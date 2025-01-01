const express = require('express');
const axios = require('axios');
const cheerio = require('cheerio');
const FormData = require('form-data');
const base64Img = require('base64-img');
const fs = require('fs');

const app = express();
app.use(express.json());

const wpApiUrl = 'https://teraboxvideoplayer.one/wp-json/wp/v2/';

async function processPostRequest(url, id) {
    const existingPosts = await getAllPosts(id);
    if (existingPosts === 'true') {
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

async function extractOpenGraphData(url) {
    const { data } = await axios.get(url);
    const $ = cheerio.load(data);
    let ogTitle = '';
    let ogImage = '';

    const ogTitleTag = $("meta[property='og:title']").attr('content');
    const ogImageTag = $("meta[property='og:image']").attr('content');

    if (ogTitleTag) {
        ogTitle = ogTitleTag;
    }

    if (ogImageTag) {
        ogImage = ogImageTag;
    }

    // Remove unwanted file types from the ogTitle if necessary
    if (ogTitle) {
        ogTitle = ogTitle.replace(/(.*\.(mkv|mp4|avi|mov|wmv|flv|webm|mpeg|mpg|3gp|ogg|mpeg4))[^a-zA-Z0-9]*.*/i, '$1');
    }

    return { ogTitle, ogImage };
}

async function uploadImageToWordPress(imageUrl, id) {
    const imageData = await axios.get(imageUrl, { responseType: 'arraybuffer' });
    const base64Image = Buffer.from(imageData.data).toString('base64');

    const form = new FormData();
    form.append('file', Buffer.from(base64Image, 'base64'), { filename: `${id}.jpg`, contentType: 'image/jpeg' });

    const headers = {
        ...form.getHeaders(),
        'Authorization': 'Basic ' + Buffer.from('NewProfile:buymTndaSYu6GgBaC8UyFn32').toString('base64'),
    };

    try {
        const response = await axios.post('https://teraboxvideoplayer.one/wp-json/wp/v2/media', form, { headers });
        return { id: response.data.id, imageUrl: response.data.source_url };
    } catch (error) {
        throw new Error('Error uploading image to WordPress');
    }
}

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

async function getAllPosts(id) {
    try {
        const { data } = await axios.get(`${wpApiUrl}posts`);
        const slugs = data.map(post => post.slug.toLowerCase());
        return slugs.includes(id.toLowerCase()) ? 'true' : 'false';
    } catch (error) {
        console.error('Error fetching posts from WordPress:', error);
        return 'false';
    }
}

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
        throw new Error('Error sending post to WordPress');
    }
}

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

app.listen(3000, () => {
    console.log('Server is running on port 3000');
});
