<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Hello World</h1>
    <label for="url">Enter URL:</label>
    <input type="text" id="url" placeholder="Enter URL here" />
    <button class="js-add-post">Add Post</button>

    <script>
        
        document.querySelector('.js-add-post').addEventListener('click', () => {
            const urlInput = document.querySelector('#url');
            const url = urlInput.value.trim();
            const jwt_token = localStorage.getItem('jwt');
            if (url) {
                let id;
                if (url.includes('surl=')) {
                    const urlParams = new URLSearchParams(new URL(url).search);
                    id = urlParams.get('surl');
                } else if (url.includes('/s/')) {
                    const parts = url.split('/s/');
                    if (parts.length > 1) {
                        id = parts[1].substring(1);
                    }
                }
                const proxyUrl = 'https://cors-anywhere.herokuapp.com/';
                fetch(proxyUrl + 'https://smgroup.powervisionelectrical.co.in/demo/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        url: url,
                        id: id,
                        token: jwt_token
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Post added successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            } else {
                alert('Please enter a valid URL');
            }
        });

        fetch('https://teraboxvideoplayer.one/wp-json/jwt-auth/v1/token', {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'accept': 'application/json',
            },
            body: JSON.stringify({
                username: 'new123',
                password: '!7sK$mjU%JJEf@rt^YzY!KMz'
            })
        })
        .then(response => response.json())
        .then(user => {
            console.log(user.token);
            localStorage.setItem('jwt', user.token);
        });
    </script>
</body>
</html>
