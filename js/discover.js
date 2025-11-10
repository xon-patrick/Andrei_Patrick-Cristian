const API_KEY = "e248a5fff5839613f5ada67376c45422";
const BASE_URL = "https://api.themoviedb.org/3";
const IMG_URL = "https://image.tmdb.org/t/p/w500";

const movieGrid = document.getElementById("movie-grid");
const searchInput = document.getElementById("search-input");
const searchBtn = document.getElementById("search-btn");
const sectionTitle = document.getElementById("section-title");

document.addEventListener("DOMContentLoaded", () => {
  getLatestMovies();
});

async function getLatestMovies() {
  sectionTitle.textContent = "Latest Movies";
  movieGrid.className = "movie-grid";
  movieGrid.innerHTML = `<p class="loading">Loading...</p>`;

  try {
    const res = await fetch(`${BASE_URL}/movie/now_playing?api_key=${API_KEY}&language=en-US&page=1`);
    const data = await res.json();
    movieGrid.innerHTML = "";
    data.results.forEach((movie) => {
      const card = document.createElement("div");
      card.classList.add("movie-card");
      card.innerHTML = `
        <img src="${movie.poster_path ? IMG_URL + movie.poster_path : "https://via.placeholder.com/300x450?text=No+Image"}" alt="${movie.title}">
        <p>${movie.title}</p>
      `;
      movieGrid.appendChild(card);
    });
  } catch (err) {
    console.error(err);
    movieGrid.innerHTML = `<p>Failed to load movies.</p>`;
  }
}

searchBtn.addEventListener("click", handleSearch);
searchInput.addEventListener("keypress", (e) => e.key === "Enter" && handleSearch());

async function handleSearch() {
  const query = searchInput.value.trim();
  if (!query) return;
  sectionTitle.textContent = `Results for "${query}"`;
  movieGrid.className = "movie-list";
  movieGrid.innerHTML = `<p class="loading">Searching...</p>`;

  try {
    const res = await fetch(`${BASE_URL}/search/movie?api_key=${API_KEY}&language=en-US&query=${encodeURIComponent(query)}`);
    const data = await res.json();
    if (data.results.length === 0) {
      movieGrid.innerHTML = `<p>No results found.</p>`;
      return;
    }

    // Sort results by popularity descending
    const sortedResults = data.results.sort((a, b) => b.popularity - a.popularity);

    movieGrid.innerHTML = "";
    for (const movie of sortedResults) {
      await renderDetailedCard(movie);
    }
  } catch (err) {
    console.error(err);
    movieGrid.innerHTML = `<p>Search failed.</p>`;
  }
}

// --- Render search results (detailed cards) ---
async function renderDetailedCard(movie) {
  const poster = movie.poster_path
    ? IMG_URL + movie.poster_path
    : "https://via.placeholder.com/300x450?text=No+Image";
  const year = movie.release_date ? movie.release_date.split("-")[0] : "N/A";
  const rating10 = movie.vote_average ? movie.vote_average.toFixed(1) : "N/A";
  const rating5 = movie.vote_average ? Math.round(movie.vote_average / 2) : 0;
  const overview = movie.overview ? movie.overview.slice(0, 150) + "..." : "No description available.";

  // Get director
  let director = "Unknown";
  try {
    const credits = await fetch(`${BASE_URL}/movie/${movie.id}/credits?api_key=${API_KEY}`);
    const creditsData = await credits.json();
    const dirObj = creditsData.crew.find((p) => p.job === "Director");
    if (dirObj) director = dirObj.name;
  } catch {}

  const stars = Array.from({ length: 5 })
    .map((_, i) => `<span class="star ${i < rating5 ? "filled" : ""}">★</span>`)
    .join("");

  const card = document.createElement("div");
  card.classList.add("result-card");
  card.innerHTML = `
    <img class="result-poster" src="${poster}" alt="${movie.title}">
    <div class="result-details">
      <div>
        <div class="result-title">${movie.title} <span class="result-sub">– ${year}</span></div>
        <div class="result-sub">de ${director}</div>
        <div class="rating">${stars} ${rating10}/10</div>
        <div class="result-overview">${overview}</div>
      </div>
    </div>
  `;
  movieGrid.appendChild(card);
}
