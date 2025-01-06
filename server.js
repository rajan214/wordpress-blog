const express = require('express');
const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');
const cors = require('cors');
const puppeteer = require('puppeteer');
const app = express();
app.use(cors());
app.use(express.json());

const wpApiUrl = 'https://teraboxvideoplayer.one/wp-json/wp/v2/';

// Function to process the incoming POST request
async function processPostRequest(url, id) {
    const existingPosts = await getAllPosts(id);
    if (existingPosts === true) {
        return { status: 'error', message: 'Post already exists' };
    }

    const ogData = await extractOpenGraphData(url);

    if (!ogData.ogImage) {
        return { status: 'error', message: 'No image found in Open Graph data' };
    }

    const imageUrl = await uploadImageToWordPress(ogData.ogImage, id);

    const postData = {
        title: ogData.ogTitle,
        content: generatePostContent(url, imageUrl.imageUrl,ogData.ogTitle),
        slug: id,
        status: 'publish',
        featured_media: imageUrl.id,
    };

    const response = await sendPostToWordPress(postData);
    return { status: 'success', message: 'Post added successfully', data: response };
}

// Function to extract Open Graph data from a URL
async function extractOpenGraphData(url) {
    const { data } = await axios.get(url);

    const ogTitleMatch = data.match(/<meta property="og:title" content="([^"]+)"/);
    const ogImageMatch = data.match(/<meta property="og:image" content="([^"]+)"/);

    let ogTitle = ogTitleMatch ? ogTitleMatch[1] : '';
    const ogImage = ogImageMatch ? ogImageMatch[1] : '';

    // Clean up ogTitle if necessary
    if (ogTitle) {
        ogTitle = ogTitle.replace(/(.*\.(mkv|mp4|avi|mov|wmv|flv|webm|mpeg|mpg|3gp|ogg|mpeg4))[^a-zA-Z0-9]*.*/i, '$1');
    }

    return { ogTitle, ogImage };
}

// Function to upload an image to WordPress
async function uploadImageToWordPress(imageUrl, id) {
    try {
        const imageData = await axios.get(imageUrl, { responseType: 'arraybuffer' });
        const base64Image = Buffer.from(imageData.data).toString('base64');

        const form = new FormData();
        form.append('file', Buffer.from(base64Image, 'base64'), { filename: `${id}.jpg`, contentType: 'image/jpeg' });

        const headers = {
            ...form.getHeaders(),
            'Authorization': 'Basic ' + Buffer.from('NewProfile:buymTndaSYu6GgBaC8UyFn32').toString('base64'),
        };

        const response = await axios.post(`${wpApiUrl}media`, form, { headers });
        return { id: response.data.id, imageUrl: response.data.source_url };
    } catch (error) {
        throw new Error('Error uploading image to WordPress: ' + error.message);
    }
}

// Function to generate post content
function generatePostContent(url, imageUrl,imageTitle) {
    return `
        <img src="${imageUrl}" alt="${imageTitle}" /><br />
        <p>📮 𝐅𝐮𝐥𝐥 𝐕𝐢𝐝𝐞𝐨 𝐋𝐢𝐧𝐤 📮</p>
        <p>
            <a href="${url}" target="_blank">
                Watch Full Video Online
            </a>
        </p>
    `;
}

// Function to check if the post already exists
async function getAllPosts(id) {
    try {
        const slugs = [];
        let page = 1;
        let totalPages = 1; // Set an initial value for totalPages
    
        // Loop to fetch posts from all pages
        while (page <= totalPages) {
          // Define the API URL with pagination
          const url = `https://teraboxvideoplayer.one/wp-json/wp/v2/posts?per_page=100&page=${page}`;
          
          // Fetch data from the API
          const response = await axios.get(url, {
            headers: {
              'Accept': 'application/json'
            }
          });
    
          // Extract the total pages from the response headers
          totalPages = parseInt(response.headers['x-wp-totalpages'], 10);
    
          // Check if there are any posts returned
          if (response.data.length === 0) {
            break;  // Stop if no posts are returned
          } else {
            // Extract the slugs from the posts and add them to the array
            const pageSlugs = response.data.map(post => post.slug.toLowerCase());
            slugs.push(...pageSlugs);
            
            // Move to the next page
            page++;
          }
        }
    
        return slugs.includes(id.toLowerCase());
    
      } catch (error) {
        console.error('Error fetching data:', error);
        return false;
      }
  }

// Function to send post to WordPress
async function sendPostToWordPress(data) {
    try {
        const response = await axios.post(`${wpApiUrl}posts`, data, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Basic ' + Buffer.from('NewProfile:buymTndaSYu6GgBaC8UyFn32').toString('base64'),
            },
        });
        return response.data;
    } catch (error) {
        console.error('Error sending post to WordPress:', error);
        throw new Error('Error sending post to WordPress: ' + error.message);
    }
}

// POST request handler for the server
app.post('/', async (req, res) => {
    const { url, id } = req.body;

    if (url && id) {
        try {
            const response = await processPostRequest(url, id);
            res.json(response);
        } catch (error) {
            res.json({ status: 'error', message: error.message });
        }
    } else {
        res.json({ status: 'error', message: 'Invalid data' });
    }
});

app.post('/getVideoURLs', async (req, res) => {
    const { iframeUrl } = req.body;

    if (!iframeUrl) {
        return res.status(400).json({ error: 'Iframe URL is required' });
    }

    try {
        const browser = await puppeteer.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        });
        const page = await browser.newPage();
        let urls = [];
        let downloadURL = '';
        page.on('response', (response) => {
            const requestUrl = response.url();
            const resourceType = response.request().resourceType();

            if ((resourceType === 'fetch' || resourceType === 'xhr') &&
                requestUrl.startsWith('https://www.1024terabox.com/share/extstreaming.m3u8?')) {
                
                urls.push(requestUrl);
                downloadURL = requestUrl;
            }
        });

        await page.goto(iframeUrl, { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('iframe');
        await new Promise((resolve) => setTimeout(resolve, 10000));
        await browser.close();
        res.json(downloadURL);
    } catch (error) {
        console.error('Error:', error.message);
        res.status(500).json({ error: 'An error occurred while processing the request.' });
    }
});

// Start the server
app.listen(3000, () => {
    console.log('Server is running on port 3000');
});
