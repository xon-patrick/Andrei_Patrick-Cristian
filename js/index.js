const API_KEY = "e248a5fff5839613f5ada67376c45422";
const BASE_URL = "https://api.themoviedb.org/3";
const IMG_URL = "https://image.tmdb.org/t/p/w500";
const PLACEHOLDER = "https://via.placeholder.com/300x450?text=No+Image";

const movieGrid = document.getElementById("movie-grid");
const activityContainer = document.getElementById("activity-container");

document.addEventListener("DOMContentLoaded", () => {
  loadPopular();
  if (activityContainer) {
    loadFollowingActivity();
  }
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

async function loadFollowingActivity() {
  try {
    const response = await fetch('get_following_activity.php');
    const data = await response.json();
    
    console.log('Activity response:', data);
    
    if (!data.success || !data.activity || data.activity.length === 0) {
      activityContainer.innerHTML = `<p style="color:#999;text-align:center;padding:2rem">No recent activity from people you follow.</p>`;
      return;
    }
    
    activityContainer.innerHTML = '';
    
    data.activity.forEach(item => {
      const card = createActivityCard(item);
      activityContainer.appendChild(card);
    });
    
  } catch (error) {
    console.error('Error loading activity:', error);
    activityContainer.innerHTML = `<p style="color:#f66;text-align:center;padding:2rem">Failed to load activity.</p>`;
  }
}

function createActivityCard(item) {
  const card = document.createElement('div');
  card.className = 'activity-card';
  
  const poster = item.poster_url || 'https://via.placeholder.com/100x150?text=No+Image';
  const avatar = item.profile_picture || 'profile.jpeg';
  const rating = item.rating ? parseInt(item.rating) : 0;
  const notes = item.notes || '';
  const watchedDate = new Date(item.watched_at);
  const timeAgo = getTimeAgo(watchedDate);
  
  let starsHtml = '';
  const normalizedRating = Math.round(rating / 2); 
  for (let i = 1; i <= 5; i++) {
    starsHtml += `<span class="activity-star ${i <= normalizedRating ? 'filled' : ''}">★</span>`;
  }
  
  card.innerHTML = `
    <div class="activity-header">
      <img src="${avatar}" alt="${escapeHtml(item.username)}" class="activity-avatar" onerror="this.src='profile.jpeg'">
      <div class="activity-user-info">
        <a href="view_profile.php?user_id=${item.user_id}" class="activity-username">${escapeHtml(item.username)}</a>
        <span class="activity-action">watched</span>
        <span class="activity-time">${timeAgo}</span>
      </div>
    </div>
    
    <div class="activity-content">
      <a href="film.php?id=${item.tmdb_id}" class="activity-poster-link">
        <img src="${poster}" alt="${escapeHtml(item.title)}" class="activity-poster">
      </a>
      
      <div class="activity-details">
        <a href="film.php?id=${item.tmdb_id}" class="activity-film-title">${escapeHtml(item.title)}</a>
        
        ${rating > 0 ? `
          <div class="activity-rating">
            ${starsHtml}
            <span class="activity-rating-number">${rating}</span>
          </div>
        ` : ''}
        
        ${notes ? `
          <p class="activity-review">"${escapeHtml(notes.substring(0, 150))}${notes.length > 150 ? '...' : ''}"</p>
        ` : ''}
      </div>
    </div>
  `;
  
  return card;
}

function getTimeAgo(date) {
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
  return date.toLocaleDateString();
}