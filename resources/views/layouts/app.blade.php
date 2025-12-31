<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Holy Bible')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CDN -->
<!--    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    -->
    <link href="{{asset('css/bootstrap-5.3.2.min.css')}}" rel="stylesheet">
    <link href="{{asset('css/select2.min.css')}}" rel="stylesheet" />
    
    <style>
        .bible-sidebar {
            position: sticky;
            top: 10px;
            height: calc(100vh - 20px);
            overflow: hidden;
            border-left: 5px solid #0dcaf0;
            border-top: 2px solid #0dcaf0;
            border-right: 2px solid #0dcaf0;
            border-bottom: 5px solid #0dcaf0;
            border-radius: 15px; 
            background-color: #fff; 
            padding: 10px;
            border-right: 1px solid #ddd;
            min-height: 200px; height: auto
        }

        .bible-content {
            height: calc(100vh - 20px);
            overflow-y: auto;
            padding: 15px; 
        }
        .main-content{
            border-right: 5px solid #0dcaf0;
            border-top: 2px solid #0dcaf0;
            border-left: 1px thin #0dcaf0;
            border-bottom: 5px solid #0dcaf0;
            border-radius: 15px; 
            background-color: #fff; 
        }
    </style>

</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed">
    <div class="container">
        <a class="navbar-brand" href="/bible">📖 Holy Bible</a>

        <form method="GET" action="javascript:void(0)" class="d-flex">
            <input
                class="form-control "                
                id="scripture-search"
                name="keyword"
                placeholder="Search scripture..." style="font-size:1.2rem; height:45px" />
            <!--<button class="btn btn-warning">Search</button>-->
            
            <div class="btn-group mb-3  w-100"  style="height:45px"  role="group">
                <input type="radio" class="btn-check" name="searchMode" id="mode-phrase" value="phrase" checked>
                <label class="btn btn-outline-primary" for="mode-phrase">Phrase</label>

                <input type="radio" class="btn-check" name="searchMode" id="mode-exact" value="exact">
                <label class="btn btn-outline-primary" for="mode-exact">Exact</label>
            </div>

            
        </form>
    </div>
</nav>

<div class="container my-4">
    @yield('content')
</div>
<!-- jQuery CDN -->
<!--    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    -->
    <script src="{{asset('js/jquery-3.6.4.min.js')}}"></script>
    <script src="{{asset('js/select2.min.js')}}"></script>
    <script>

        $(function(){
          
          // Auto-load chapters for the first selected book (Genesis)
            let chapterMax = 1; // default
           
            // Limit verse input based on selected chapter
 
                // bool-select on change refined to this below
                
                $('#book-select').on('change', function(){
                    let book_id = $(this).val();
                    if (!book_id) return;

                    loadChapters(book_id);
                });
                
            $('#read-btn').on('click', function(){
                let book_id = $('#book-select').val();
                let chapter = $('#chapter-select').val();
                let verse = $('#verse-input').val();
                // let version = '{{ $version }}';
                let version = currentVersion; 

                if(!book_id || !chapter) { alert('Select book and chapter'); return; }

                $.get('{{ route("bible.read.ajax") }}', { book_id, chapter, verse, version }, function(res){
                    if(res.error){
                        $('#verse-title').text(res.error);
                        $('#verse-content').html('');
                    } else {
                        $('#verse-title').text(res.book + ' ' + res.chapter);
                        let html = '';
                        res.verses.forEach(v=>{
                            html += `<p><sup>${v.verse}</sup> ${v.text}</p>`;
                        });
                        $('#verse-content').html(html);
                    }
                });
            });

           $('#book-select').trigger('change');
           setTimeout(function(){$('#read-btn').click();},3000);
           
           // This avoids repeated AJAX calls and is much faster.
           
           // for auto search of bibles 
 
        $('#smart-search').on('keyup', function(e){
            if (e.key !== 'Enter') return;

            let input = $(this).val().trim().toLowerCase();
            if (!input) return;

            let parts = input.split(/\s+/);

            let bookPart = parts[0];
            let chapter = parts[1] || null;
            let verse = parts[2] || null;

            let book = findBookFuzzy(bookPart);

            if (!book) {
                alert('Book not found');
                return;
            }

            // Select book
            $('#book-select').val(book.id);

            // Load chapters THEN select correct chapter
            loadChapters(book.id, chapter, function(){
                if (verse) {
                    $('#verse-input').val(verse);
                }
                // Auto-read
                $('#read-btn').click();
            });
        });
         
         
         $('#version-select').on('change', function(){
            currentVersion = $(this).val();

            let book_id = $('#book-select').val();
            let chapter = $('#chapter-select').val();
            let verse = $('#verse-input').val();

            // Reload book list for version
            $.get('{{ route("bible.search.books") }}', {
                q: '',
                version: currentVersion
            }, function(data){
                allBooks = data;

                let bookSelect = $('#book-select');
                bookSelect.empty();

                data.forEach(book => {
                    bookSelect.append(
                        `<option value="${book.id}">${book.name}</option>`
                    );
                });

                if (book_id) {
                    bookSelect.val(book_id);
                    loadChapters(book_id, chapter, function(){
                        if (verse) $('#verse-input').val(verse);
                        $('#read-btn').click();
                    });
                }
            });
        });

           
        });
        
        // to implement fuzzy search like john - 
        /**  Abbreviations: jn, ps, rom
              Partial names: gen, revel 
              Numbered books: 1cor, 2tim, 3jn
        **/
        
        let allBooks = @json($books);
        let currentVersion = $('#version-select').val();

        
        function normalize(str) {
            str = str.toLowerCase().trim();

            // Convert roman numerals to digits
            const romanMap = {
                'iii': '3',
                'ii': '2',
                'i': '1'
            };

            for (let r in romanMap) {
                str = str.replace(new RegExp(`\\b${r}\\b`, 'g'), romanMap[r]);
            }

            return str
                .replace(/\s+/g, '')
                .replace(/\./g, '');
        }

        // STEP 2: FUZZY BOOK FINDER
        function findBookFuzzy(input) {
            let needle = normalize(input);

            return allBooks.find(book => {
                let bookName = normalize(book.name);

                // Direct prefix match
                if (bookName.startsWith(needle)) return true;

                // Allow "2cor" to match "2corinthians"
                if (bookName.startsWith(needle.replace(/\d+$/, ''))) return true;

                return false;
            });
        }

 
        //  for scripture search
        
        function highlight(q, text) {
            let words = q.split(/\s+/).join('|');
            let regex = new RegExp(`(${words})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }
        
        /// handle book and chapter selection after search 
        function loadChapters(book_id, selectedChapter = null, callback = null) {
            // let version = '{{ $version }}';
             let version = currentVersion; 

            $.get('{{ route("bible.book.info") }}', { book_id, version }, function(res){
                if(res.error){
                    alert(res.error);
                    return;
                }

                let chapterSelect = $('#chapter-select');
                chapterSelect.empty();
                //chapterSelect.append('<option value="">Select Chapter</option>');

                for(let i = 1; i <= res.maxChapter; i++){
                    chapterSelect.append(
                        `<option value="${i}">${i}</option>`
                    );
                }

                if (selectedChapter) {
                    chapterSelect.val(selectedChapter).trigger('change');
                }

                if (callback) callback();
            });
        }
        
        // scripture search
        $('#scripture-search').on('keyup', function(e){
            if (e.key !== 'Enter') return;

            let q = $(this).val().trim();
            if (q.length < 3) return;

            let mode = $('input[name="searchMode"]:checked').val();

            $('#search-results').html('<em>Searching…</em>');

            $.get('{{ route("bible.search.scripture") }}', {
                q: q,
                mode: mode,
                version: currentVersion
            }, function(res){

                if (res.length === 0) {
                    $('#search-results').html('<p>No results found.</p>');
                     $('#verse-content').html('');
                    return;
                }

                let html = `<h6>${res.length} Results</h6><ul class="list-group">`;
                let k=1;
                res.forEach(r => {
                    html += `
                        <li class="list-group-item scripture-result"
                            data-book="${r.book_id}"
                            data-chapter="${r.chapter}"
                            data-verse="${r.verse}">
                           <sup>`+k+` </sup> <strong>${r.book} ${r.chapter}:${r.verse}</strong><br>
                            ${highlight(q, r.text)}
                        </li>
                    `; k++;
                });

                html += '</ul>';
                $('#search-results').html(html);
               
                
            });
        });

        
        $(document).on('click', '.scripture-result', function(){
            let book_id = $(this).data('book');
            let chapter = $(this).data('chapter');
            let verse   = $(this).data('verse');

            $('#book-select').val(book_id);

            loadChapters(book_id, chapter, function(){
                $('#verse-input').val(verse);
                $('#read-btn').click();
            });
        });


    </script>
</body>
</html>
