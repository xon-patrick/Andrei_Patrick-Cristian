const API_KEY = "e248a5fff5839613f5ada67376c45422";
const BASE_URL = "https://api.themoviedb.org/3";
const IMG_URL = "https://image.tmdb.org/t/p/w500";
const PLACEHOLDER = "https://via.placeholder.com/300x450?text=No+Image";

const movieGrid = document.getElementById("movie-grid");

document.addEventListener("DOMContentLoaded", () => {
  loadPopular();
});

async function loadPopular() {
  movieGrid.innerHTML = `<p class="loading" style="color:#ccc;text-align:center;padding:24px">Loading popular films…</p>`;
  try {
    const res = await fetch(`${BASE_URL}/movie/popular?api_key=${API_KEY}&language=en-US&page=1`);
    if (!res.ok) throw new Error(`TMDB error ${res.status}`);
    const data = await res.json();
    movieGrid.innerHTML = "";
    const movies = (data.results || []).slice(0, 10);
    if (movies.length === 0) {
      movieGrid.innerHTML = `<p style="color:#ccc;text-align:center;padding:24px">No popular films found.</p>`;
      return;
    }
    movies.forEach(movie => {
      const poster = movie.poster_path ? IMG_URL + movie.poster_path : PLACEHOLDER;
      const card = document.createElement("div");
      card.className = "movie-card";
      card.innerHTML = `
        <img src="${poster}" alt="${escapeHtml(movie.title)}">
        <p>${escapeHtml(movie.title)}</p>
      `;
      card.style.cursor = "pointer";
      card.addEventListener("click", () => {
        window.location.href = `film.php?id=${movie.id}`;
      });
      movieGrid.appendChild(card);
    });
  } catch (err) {
    console.error(err);
    movieGrid.innerHTML = `<p style="color:#f66;text-align:center;padding:24px">Failed to load popular films.</p>`;
  }
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}