require('dotenv').config();
const express = require('express');
const lessMiddleware = require('less-middleware');
const axios = require('axios');
const { makeImage } = require('./gd');

const path = require('path')

const app = express();

app.use(lessMiddleware(path.join(__dirname, "source"), {
    dest: path.join(__dirname, "public"),
}));
app.use(express.static(__dirname + '/public'));
app.set('view engine', 'ejs');
app.set('views', './views');
app.use(express.urlencoded({ extended: true }));

const LASTFM_TOKEN = process.env.LASTFM_TOKEN;
const MAX_PER_PAGE = 500;

const validPeriods = ['12month', '6month', '3month', '1month', '7day'];

const sortFunction = function (a, b) {
    return b[1].playcount - a[1].playcount || a[0].localeCompare(b[0], "en")
}

const getSortedEntries = function (object) {
    return Object.entries(object).sort(sortFunction);
}

app.get('/', (req, res) => {
    const period = validPeriods.includes(req.query.period) ? req.query.period : 'overall';
    res.render('index', { period });
});

app.get('/results', async (req, res) => {
    try {
        const user1 = req.query.user1 || '';
        const user2 = req.query.user2 || '';
        const period = validPeriods.includes(req.query.period) ? req.query.period : 'overall';

        if (!user1 || !user2) {
            return res.redirect('/');
        }

        const [user1ArtistCount, user2ArtistCount] = await getArtistCount(user1, user2, period);
        const user1PageCount = Math.ceil(user1ArtistCount / MAX_PER_PAGE);
        const user2PageCount = Math.ceil(user2ArtistCount / MAX_PER_PAGE);

        const [arr1, arr2] = await Promise.all([
            getUserArtists(user1, period, user1PageCount),
            getUserArtists(user2, period, user2PageCount)
        ]);

        let count1 = 0, count2 = 0;
        Object.values(arr1).forEach(val => count1 += parseInt(val.playcount));
        Object.values(arr2).forEach(val => count2 += parseInt(val.playcount));

        const commonArtists = {};
        const artistKeys1 = Object.keys(arr1);
        const artistKeys2 = Object.keys(arr2);
        
        artistKeys1.forEach(key => {
            if (key in arr2) {
                commonArtists[key] = {
                    ...arr1[key],
                    playcountUser1: arr1[key].playcount,
                    playcountUser2: arr2[key].playcount,
                    playcount: Math.min(arr1[key].playcount, arr2[key].playcount)
                };
            }
        });

        let commonArtistListenCount = 0;
        Object.values(commonArtists).forEach(val => commonArtistListenCount += parseInt(val.playcount));

        // Sort artists
        const sortedUser1Artists = getSortedEntries(arr1);
        const sortedUser2Artists = getSortedEntries(arr2);
        const sortedCommonArtists = getSortedEntries(commonArtists);
        const commonArtistsCount = sortedCommonArtists.length;

        res.render('results', {
            user1, user2, period,
            user1ArtistCount, user2ArtistCount, commonArtistsCount,
            count1, count2, commonArtistListenCount,
            user1Artists: sortedUser1Artists.slice(0, 10),
            user2Artists: sortedUser2Artists.slice(0, 10),
            commonArtists: sortedCommonArtists.slice(0, 10),
            svgImage: makeImage(count1, count2, commonArtistListenCount, arr1, arr2, sortedCommonArtists)
        });

    } catch (error) {
        console.error('Error:', error);
        res.status(500).send('An error occurred while processing your request.');
    }
});

async function getArtistCount(user1, user2, period) {
    const urls = [
        buildUrl('user.getTopArtists', user1, { period, limit: 1 }),
        buildUrl('user.getTopArtists', user2, { period, limit: 1 })
    ];

    const responses = await Promise.all(urls.map(url => axios.get(url)));
    return [
        parseInt(responses[0].data.topartists['@attr'].total),
        parseInt(responses[1].data.topartists['@attr'].total)
    ];
}

async function getUserArtists(user, period, pages) {
    const urls = [];
    for (let i = 1; i <= pages; i++) {
        urls.push(buildUrl('user.getTopArtists', user, { period, limit: MAX_PER_PAGE, page: i }));
    }

    const responses = await Promise.all(urls.map(url => axios.get(url)));
    const artists = {};

    responses.forEach(response => {
        if (response.data.topartists && response.data.topartists.artist) {
            response.data.topartists.artist.forEach(artist => {
                artists[artist.name] = {
                    name: artist.name,
                    playcount: parseInt(artist.playcount),
                    url: artist.url
                };
            });
        }
    });

    return artists;
}

function buildUrl(method, user, options = {}) {
    const baseParams = {
        user,
        method,
        api_key: LASTFM_TOKEN,
        format: 'json',
        ...options
    };
    return `https://ws.audioscrobbler.com/2.0/?${new URLSearchParams(baseParams)}`;
}

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});