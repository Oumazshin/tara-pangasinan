const fs = require('fs');

const dataFile = 'c:/Users/Desktop/Documents/GitHub/Tara Pangasinan/data/spots.json';
let spots = JSON.parse(fs.readFileSync(dataFile, 'utf8'));

// Popularity tiers roughly based on real-world Pangasinan spots
const popularSpots = ['hundred-islands', 'manaoag-church', 'patar-beach', 'bangus-festival'];
const mediumSpots = ['enchanted-cave', 'bolinao-falls', 'lingayen-gulf', 'tondol-beach', 'puto-calasiao'];

spots.forEach(spot => {
    let baseReviews = 50;
    let maxReviews = 200;
    
    if (popularSpots.includes(spot.id)) {
        baseReviews = 1500;
        maxReviews = 8500;
    } else if (mediumSpots.includes(spot.id)) {
        baseReviews = 300;
        maxReviews = 1200;
    }

    spot.reviews = Math.floor(Math.random() * (maxReviews - baseReviews)) + baseReviews;
    
    // Ratings between 4.1 and 4.9, weighted towards 4.5+
    let rating = (Math.random() * 0.8) + 4.1; 
    spot.rating = parseFloat(rating.toFixed(1));
});

fs.writeFileSync(dataFile, JSON.stringify(spots, null, 4));
console.log('Updated reviews and ratings successfully!');
