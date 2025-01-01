
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
        function extractOpenGraphData(url) {
            return fetch(url)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    let ogTitle = doc.querySelector('meta[property="og:title"]')?.getAttribute('content') || 
                                  doc.querySelector('meta[name="og:title"]')?.getAttribute('content') || 
                                  'No title found';

                    const ogImage = doc.querySelector('meta[property="og:image"]')?.getAttribute('content') || 
                                    doc.querySelector('meta[name="og:image"]')?.getAttribute('content') || '';

                    ogTitle = ogTitle.replace(/(.*\.(mkv|mp4|avi|mov|wmv|flv|webm|mpeg|mpg|3gp|ogg|mpeg4))[^a-zA-Z0-9]*.*/i, '$1');
                    return { ogTitle, ogImage };
                })
                .catch(error => {
                    console.error('Error fetching Open Graph data:', error);
                    return { ogTitle: 'Error fetching title', ogImage: '' };
                });
        }

        function uploadToWordPress(base64Image) {
            const token = localStorage.getItem('jwt');

            const byteCharacters = atob(base64Image);
            const byteArrays = [];
            for (let offset = 0; offset < byteCharacters.length; offset += 512) {
                const slice = byteCharacters.slice(offset, offset + 512);
                const byteNumbers = new Array(slice.length);
                for (let i = 0; i < slice.length; i++) {
                    byteNumbers[i] = slice.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                byteArrays.push(byteArray);
            }
            const imageBlob = new Blob(byteArrays, { type: 'image/jpeg' });

            const formDataForWP = new FormData();
            const featureImageName = 'image.jpg';

            formDataForWP.append('file', imageBlob, featureImageName);

            const wpTargetUrl = 'https://teraboxvideoplayer.one/wp-json/wp/v2/media';

            return fetch(wpTargetUrl, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
                body: formDataForWP,
            })
            .then(response => response.json())
            .then(imageData => {
                if (imageData.source_url) {
                    return imageData.source_url;
                } else {
                    throw new Error('Image upload to WordPress failed');
                }
            })
            .catch(error => {
                console.error('Error uploading to WordPress:', error);
                throw new Error('Image upload failed');
            });
        }

        function addPost(url, id) {
            const token = localStorage.getItem('jwt');

            extractOpenGraphData(url).then(data => {
                const { ogTitle, ogImage } = data;

                if (ogImage) {
                    fetch(ogImage)
                        .then(response => response.blob())
                        .then(blob => {
                            const reader = new FileReader();
                            reader.onloadend = function() {
                                const base64Image = reader.result.split(',')[1];
                                uploadToWordPress(base64Image).then(uploadedImageUrl => {
                                    const postContent = `
                                        <img src="${uploadedImageUrl}" alt="Image from the link" /><br />
                                        <p>📮 𝐅𝐮𝐥𝐥 𝐕𝐢𝐝𝐞𝐨 𝐋𝐢𝐧𝐤 📮</p>
                                        <p>
                                            <a href="https://www.teraboxplayer.online/?q=${encodeURIComponent(url)}" target="_blank">
                                                Watch Full Video Online
                                            </a>
                                        </p>
                                    `;
                                    fetch('https://teraboxvideoplayer.one/wp-json/wp/v2/posts', {
                                        method: "POST",
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'accept': 'application/json',
                                            'Authorization': `Bearer ${token}`
                                        },
                                        body: JSON.stringify({
                                            title: ogTitle || 'No title found',
                                            content: postContent,
                                            slug: id,
                                            status: 'publish'
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(post => {
                                        console.log(post); 
                                    });
                                });
                            };
                            reader.readAsDataURL(blob);
                        })
                        .catch(error => {
                            console.error('Error fetching or converting image:', error);
                        });
                } else {
                    alert('No image found in Open Graph data');
                }
            });
        }

        const addPostButton = document.querySelector('.js-add-post');
        addPostButton.addEventListener('click', () => {
            const urlInput = document.querySelector('#url'); 
            const url = urlInput.value.trim(); 
            if (url) {
                let id;

                if (url.includes('surl=')) {
                    const urlParams = new URLSearchParams(new URL(url).search);
                    id = urlParams.get('surl');
                }
                
                else if (url.includes('/s/')) {
                    const parts = url.split('/s/');
                    if (parts.length > 1) {
                        id = parts[1].substring(1);
                    }
                }

                fetch('https://teraboxvideoplayer.one/wp-json/wp/v2/posts')
                .then(response => response.json())
                .then(posts => {
                    let ids = [];
                    posts.forEach(post => {
                        const url_ids = post.slug;
                        if (url_ids) {
                            ids.push(url_ids);
                        }
                    });
                    if (ids.some(postId => postId.toLowerCase() === id.toLowerCase())) {
                        alert('Already Have this data');
                    } else {
                        addPost(url, id);
                    }
                })
                .catch(error => {
                    console.error('Error fetching posts:', error);
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
                username: 'REST1',
                password: 'n9i%HWX9^S!SlA)P2Ux%xwtb'
            })
        })
        .then(response => response.json())
        .then(user => {
            localStorage.setItem('jwt', user.token);
        });
    </script>
</body>
</html>
