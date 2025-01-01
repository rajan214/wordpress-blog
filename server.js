const express = require('express');
const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');

const app = express();
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
        content: generatePostContent(url, imageUrl.imageUrl),
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

    const ogTitle = ogTitleMatch ? ogTitleMatch[1] : '';
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
function generatePostContent(url, imageUrl) {
    return `
        <img src="${imageUrl}" alt="Image from the link" /><br />
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
        const { data } = await axios.get(`${wpApiUrl}posts`);
        const slugs = data.map(post => post.slug.toLowerCase());
        return slugs.includes(id.toLowerCase()); // Returns true if exists
    } catch (error) {
        console.error('Error fetching posts from WordPress:', error);
        return false; // Return false if there was an error
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

// Start the server
app.listen(3000, () => {
    console.log('Server is running on port 3000');
});
