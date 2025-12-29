<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Holy Bible')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .bible-sidebar {
            position: sticky;
            top: 10px;
            height: calc(100vh - 20px);
            overflow: hidden;
            background: #fff;
            padding: 10px;
            border-right: 1px solid #ddd;
        }

        .bible-content {
            height: calc(100vh - 20px);
            overflow-y: auto;
            padding: 15px;
        }
    </style>

</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed">
    <div class="container">
        <a class="navbar-brand" href="/bible">📖 Holy Bible</a>

        <form method="GET" action="/bible/search" class="d-flex">
            <input
                class="form-control me-2"
                name="keyword"
                placeholder="Search scripture..."
                required
            >
            <button class="btn btn-warning">Search</button>
        </form>
    </div>
</nav>

<div class="container my-4">
    @yield('content')
</div>
<!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

    </script>
</body>
</html>
