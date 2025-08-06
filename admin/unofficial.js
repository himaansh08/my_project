const axios = require('axios');

const apiKey = "AIzaSyCHEBWcw4XQznC8XquDfwNVtc74rLmIU6s";
const searchEngineId ="b07a56f4aa2784be6";

const excludedSites = [
  'instagram.com',
  'youtube.com'
];

const boosterKeywords = [
  'article',
  'news',
  '"press release"',
  'blog'
];
async function fetchAllResults(keyword, startDate, endDate, totalResults = 100) {
  if (totalResults > 100) {
    console.warn("API limit is 100 results. Fetching 100.");
    totalResults = 100;
  }
  
  let allResults = [];
  const numPerPage = 10;
  
  // Create the base query string
  let baseQuery = `"${keyword}"`;
  const boosterQueryPart = `(${boosterKeywords.join(' OR ')})`;
  baseQuery += ` ${boosterQueryPart}`;

  // Add the date range operators to the query string
  baseQuery += ` after:${startDate} before:${endDate}`;
  
  const exclusionQueryPart = excludedSites.map(site => `-site:${site}`).join(' ');
  if (exclusionQueryPart) {
    baseQuery += ` ${exclusionQueryPart}`;
  }

  const url = `https://www.googleapis.com/customsearch/v1`;

  try {
    for (let i = 0; i < totalResults; i += numPerPage) {
      const startIndex = i + 1;
      
      const response = await axios.get(url, {
        params: {
          key: apiKey,
          cx: searchEngineId,
          q: baseQuery,
          sort: 'date', // Sort by date to get the most relevant first
          num: numPerPage,
          start: startIndex,
          // dateRestrict: 'w10',
        }
      });
      
      const items = response.data.items || [];
      if (items.length > 0) {
        allResults = allResults.concat(items);
      }
      
      if (items.length < numPerPage) {
        break; 
      }
    }
    
    console.log(`Search successful. Fetched ${allResults.length} results for keyword: "${keyword}"`);
    return allResults;

  } catch (error) {
    const apiError = error.response ? error.response.data.error : error.message;
    console.error('Error fetching search results:', apiError);
    throw new Error('Failed to fetch search results from Google API.');
  }
}

// --- Example Usage ---
// This will fetch all available results (up to 100) for "renewable energy"
// published between January 1st, 2025, and June 30th, 2025.
//
// fetchAllResults("renewable energy", "2025-01-01", "2025-06-30").then(results => {
//   console.log(`Total results found: ${results.length}`);
// });


module.exports = { fetchAllResults };