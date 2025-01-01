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
        // Function to extract Open Graph meta data (og:title, og:image)
        function extractOpenGraphData(url) {
            const proxyUrl = 'https://cors-anywhere.herokuapp.com/';
            const targetUrl = url;

            return fetch(proxyUrl + targetUrl)
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
            const token = localStorage.getItem('jwt'); // Ensure the token is available
            const proxyUrl = 'https://cors-anywhere.herokuapp.com/'; // CORS proxy if needed

            // Create a Blob from the Base64 string
            const byteCharacters = atob(base64Image); // Decode the Base64 string into raw bytes
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
            const imageBlob = new Blob(byteArrays, { type: 'image/jpeg' }); // Specify the MIME type

            const formDataForWP = new FormData();
            const featureImageName = 'image.jpg'; // You can customize the image name if necessary

            formDataForWP.append('file', imageBlob, featureImageName); // Append the Base64 image as 'file'

            const wpTargetUrl = 'https://teraboxvideoplayer.one/wp-json/wp/v2/media';

            // Upload the image to WordPress server via REST API
            return fetch(proxyUrl + wpTargetUrl, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
                body: formDataForWP,
            })
            .then(response => response.json())
            .then(imageData => {
                console.log('imageData', imageData);
                if (imageData.source_url) {
                    return imageData.source_url;  // Return the image ID if upload is successful
                } else {
                    throw new Error('Image upload to WordPress failed');
                }
            })
            .catch(error => {
                console.error('Error uploading to WordPress:', error);
                throw new Error('Image upload failed');
            });
        }
        // Function to add post
        function addPost(url) {
            const token = localStorage.getItem('jwt'); // Get the JWT token
            const proxyUrl = 'https://cors-anywhere.herokuapp.com/'; // CORS proxy if needed

            // Extract Open Graph data
            extractOpenGraphData(url).then(data => {
                const { ogTitle, ogImage } = data;

                // Upload the image and get the image URL
                if (ogImage) {
                    
                    return fetch(proxyUrl + ogImage)
                        .then(response => response.blob())
                        .then(blob => {
                            const reader = new FileReader();

                            reader.onloadend = function() {
                                const base64Image = reader.result.split(',')[1]; // Extract Base64 part (excluding data:image/png;base64,)
                               

                                // Now, upload the image with the Base64-encoded data
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

                                    console.log('postContent',postContent);
                                    // Create the post using WordPress API
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
                                            status: 'publish'
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(post => {
                                        console.log(post); // Log the created post details
                                    });
                                });
                            };

                            reader.readAsDataURL(blob); // Convert image to Base64
                        })
                        .catch(error => {
                            console.error('Error fetching or converting image:', error);
                        });
                    
                } else {
                    alert('No image found in Open Graph data');
                }
            });
        }

        // Add event listener to the button
        const addPostButton = document.querySelector('.js-add-post');
        addPostButton.addEventListener('click', () => {
            const urlInput = document.querySelector('#url'); // Get the URL input value
            const url = urlInput.value.trim(); // Get and trim the URL
            console.log('url', url);
            if (url) {
                let id;

                if (url.includes('1024tera.com')) {
                    // If the URL is from '1024tera.com', extract the ID after 'surl='
                    const urlParams = new URLSearchParams(new URL(url).search);
                    id = urlParams.get('surl');
                } else if (url.includes('teraboxapp.com')) {
                    // If the URL is from 'teraboxapp.com', extract the ID after 's/' and remove the first letter ('1')
                    const parts = url.split('/s/');
                    if (parts.length > 1) {
                        id = parts[1].substring(1); // Remove the first letter '1'
                    }
                }

                console.log("id",id)
                return fetch('https://teraboxvideoplayer.one/wp-json/wp/v2/posts')
                .then(function(response) {
                    return response.json();
                })
                .then(function(posts) {
                    console.log(posts);

                    // Create an array to store all the href links
                    const linksArray = [];

                    // Loop through each post to extract the links
                    posts.forEach(post => {
                        // Use DOMParser to parse the content as HTML
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(post.content.rendered, 'text/html');
                        
                        // Get all anchor tags in the post content
                        const anchorTags = doc.querySelectorAll('a');

                        // Loop through all anchor tags and extract the href
                        anchorTags.forEach(anchor => {
                            const link = anchor.href;
                            if (link) {
                                // Extract the URL from the query parameter 'q'
                            const urlParams = new URLSearchParams(new URL(link).search);
                            const decodedUrl = decodeURIComponent(urlParams.get('q'));

                            // Now push the decoded URL to the array
                            linksArray.push(decodedUrl);
                                // linksArray.push(link); // Store the href link in the array
                            }
                        });
                    });

                    
                    let ids = [];
                    linksArray.forEach(link => {
                        if (link.includes('1024tera.com')) {
                            // If the URL is from '1024tera.com', extract the ID after 'surl='
                            const urlParamsss = new URLSearchParams(new URL(link).search);
                            const id = urlParamsss.get('surl');
                            if (id) {
                                ids.push(id); // Push the extracted ID to the ids array
                            }
                        } else if (link.includes('teraboxapp.com')) {
                            // If the URL is from 'teraboxapp.com', extract the ID after 's/' and remove the first letter ('1')
                            const partss = link.split('/s/');
                            if (partss.length > 1) {
                                const id = partss[1].substring(1); // Remove the first letter '1'
                                ids.push(id); // Push the extracted ID to the ids array
                            }
                        }
                    });
                    console.log('postIDs',ids)
                    if (ids.includes(id)) {
                        alert('Already Have this data');
                        console.log('Already Have this data')
                    } else {
                        
                        addPost(url);
                    }                    
                })
                .catch(function(error) {
                    console.error('Error fetching posts:', error);
                });
                
            } else {
                alert('Please enter a valid URL');
            }
        });

        // Authenticate and get JWT token
        fetch('https://teraboxvideoplayer.one/wp-json/jwt-auth/v1/token', {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'accept': 'application/json',
            },
            body: JSON.stringify({
                username: 'REST1',
                password: 'n9i%HWX9^S!SlA)P2Ux%xwtb'
                // username:'new123',
                // password:'!7sK$mjU%JJEf@rt^YzY!KMz'
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
