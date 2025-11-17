document.addEventListener('DOMContentLoaded', async () => {
  const API_KEY = "e248a5fff5839613f5ada67376c45422";
  const BASE_URL = "https://api.themoviedb.org/3";
  const IMG_BASE = "https://image.tmdb.org/t/p/";

  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  if (!id) {
    document.getElementById('filmTitle').textContent = 'Movie not specified';
    return;
  }

  const filmHeader = document.getElementById('filmHeader');
  const thumbImg = document.getElementById('filmThumbImg');
  const titleEl = document.getElementById('filmTitle');
  const metaEl = document.getElementById('filmMeta');
  const taglineEl = document.getElementById('filmTagline');
  const overviewEl = document.getElementById('filmOverview');
  const reviewsContainer = document.getElementById('reviewsContainer');

  // loading state
  titleEl.textContent = 'Loading…';
  overviewEl.textContent = '';

  try {
    const res = await fetch(`${BASE_URL}/movie/${id}?api_key=${API_KEY}&language=en-US&append_to_response=credits,reviews,images`);
    if (!res.ok) throw new Error('Failed to fetch movie details');
    const details = await res.json();

    // set page title
    document.title = `${details.title} — Journel`;

    // backdrop for header (prefer backdrop, fallback to poster)
    const backdropPath = details.backdrop_path || details.poster_path;
    if (backdropPath) {
      const imgUrl = `${IMG_BASE}w1280${backdropPath}`;
      filmHeader.style.backgroundImage = `linear-gradient(to bottom, rgba(20,24,28,0) 0%, rgba(20,24,28,0.8) 70%, #14181c 100%), url('${imgUrl}')`;
    }

    // poster thumbnail
    if (details.poster_path) {
      thumbImg.src = `${IMG_BASE}w342${details.poster_path}`;
      thumbImg.alt = details.title;
    } else {
      thumbImg.src = 'https://via.placeholder.com/300x450?text=No+Image';
    }

    // title, tagline, overview
    titleEl.textContent = details.title || '—';
    taglineEl.textContent = details.tagline || '';
    overviewEl.textContent = details.overview || 'No description available.';

    const existingRating = document.querySelector('.filmRating');
    if (existingRating) existingRating.remove();

    const rating10 = details.vote_average ? details.vote_average.toFixed(1) : 'N/A';
    const rating5 = details.vote_average ? Math.round(details.vote_average / 2) : 0;
    const starsHtml = Array.from({ length: 5 })
      .map((_, i) => `<span class="star ${i < rating5 ? 'filled' : ''}">★</span>`)
      .join('');

    const votesCount = details.vote_count ? `(${details.vote_count.toLocaleString()} votes)` : '';

    const ratingContainer = document.createElement('div');
    ratingContainer.className = 'filmRating';
    ratingContainer.innerHTML = `
      <div class="filmRating-left">
        <div class="filmStars">${starsHtml}</div>
        <div class="filmGrade">${rating10}/10</div>
      </div>
      <div class="filmRating-sub">${votesCount}</div>
    `;

    const synopsisSection = document.querySelector('.filmSynopsis');
    if (synopsisSection) {
      synopsisSection.parentNode.insertBefore(ratingContainer, synopsisSection);
    } else {
      metaEl.parentNode.insertBefore(ratingContainer, metaEl.nextSibling);
    }

    // director
    let director = 'Unknown';
    if (details.credits && Array.isArray(details.credits.crew)) {
      const dir = details.credits.crew.find(c => c.job === 'Director' || c.department === 'Directing');
      if (dir) director = dir.name;
    }

    // runtime and year
    const runtime = details.runtime ? `${details.runtime} min` : 'N/A';
    const year = details.release_date ? details.release_date.split('-')[0] : 'N/A';

    metaEl.innerHTML = `<u><b>${year}</b></u> ‧ Directed by <u><b>${director}</b></u> ‧ ${runtime}`;

    // reviewa
     reviewsContainer.innerHTML = '';
    const reviews = (details.reviews && details.reviews.results) ? details.reviews.results : [];

    if (reviews.length === 0) {
      reviewsContainer.innerHTML = `<div class="review"><p class="reviewText">No user reviews available.</p></div>`;
    } else {
      // show more reviews (limit up to 10)
      reviews.slice(0, 10).forEach(r => {
        const rev = document.createElement('div');
        rev.className = 'review';

        // author info
        const authorDetails = r.author_details || {};
        const authorName = authorDetails.username || r.author || 'Anonymous';
        const avatarPath = authorDetails.avatar_path || null;
        let avatarUrl = 'https://via.placeholder.com/48?text=U';
        if (avatarPath) {
          // TMDB sometimes stores full URL with leading slash
          if (avatarPath.startsWith('/https://') || avatarPath.startsWith('/http://')) {
            avatarUrl = avatarPath.slice(1);
          } else if (avatarPath.startsWith('http://') || avatarPath.startsWith('https://')) {
            avatarUrl = avatarPath;
          } else {
            // fallback attempt to use TMDB image service (may not always be valid)
            avatarUrl = `${IMG_BASE}w45${avatarPath}`;
          }
        }
        
                // date formatting
        const dateRaw = r.created_at || r.updated_at || null;
        const dateStr = dateRaw ? new Date(dateRaw).toLocaleDateString() : '';

        // author rating (may be null)
        const numericGrade = typeof authorDetails.rating === 'number' ? authorDetails.rating : null;
        const ratingStars = numericGrade ? Math.round(numericGrade / 2) : 0;
        const starsHtml = Array.from({ length: 5 })
          .map((_, i) => `<span class="star ${i < ratingStars ? 'filled' : ''}">★</span>`)
          .join('');

        const content = r.content ? (r.content.length > 1000 ? r.content.slice(0, 1000) + '...' : r.content) : '';

        rev.innerHTML = `
          <div class="reviewHeader" style="display:flex;gap:12px;align-items:flex-start;">
            <img class="reviewAvatar" src="${avatarUrl}" alt="${authorName}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
            <div style="flex:1">
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <strong class="reviewAuthor">${authorName}</strong>
                ${dateStr ? `<span class="reviewDate" style="color:#999;font-size:.9rem;">${dateStr}</span>` : ''}
                ${numericGrade ? `<span class="reviewGrade" style="margin-left:6px;color:#ffb86b;font-weight:600;">${numericGrade}/10</span>` : ''}
              </div>
              <div class="reviewRating" style="margin-top:6px;color:#ffd166;">${starsHtml}</div>
            </div>
          </div>

          <p class="reviewText" style="margin:10px 0 12px;color:#ddd;line-height:1.4;">${content}</p>

          <div class="review-actions" style="display:flex;gap:8px;align-items:center;">
            <button class="rev-btn like" aria-pressed="false" title="Like">👍 <span class="count">0</span></button>
            <button class="rev-btn dislike" aria-pressed="false" title="Dislike">👎 <span class="count">0</span></button>
            <button class="rev-btn comment" aria-pressed="false" title="Comment">💬</button>
          </div>
        `;

        const likeBtn = rev.querySelector('.rev-btn.like');
        const dislikeBtn = rev.querySelector('.rev-btn.dislike');
        const commentBtn = rev.querySelector('.rev-btn.comment');
        const likeCountEl = likeBtn.querySelector('.count');
        const dislikeCountEl = dislikeBtn.querySelector('.count');

                let likes = 0;
        let dislikes = 0;

        likeBtn.addEventListener('click', () => {
          const active = likeBtn.classList.toggle('active');
          likeBtn.setAttribute('aria-pressed', String(active));
          if (active) {
            likes += 1;
            if (dislikeBtn.classList.contains('active')) {
              dislikeBtn.classList.remove('active');
              dislikeBtn.setAttribute('aria-pressed', 'false');
              if (dislikes > 0) dislikes -= 1;
            }
          } else {
            if (likes > 0) likes -= 1;
          }
          likeCountEl.textContent = likes;
          dislikeCountEl.textContent = dislikes;
        });

        dislikeBtn.addEventListener('click', () => {
            const active = dislikeBtn.classList.toggle('active');
            dislikeBtn.setAttribute('aria-pressed', String(active));
            if (active) {
            dislikes += 1;
            if (likeBtn.classList.contains('active')) {
                likeBtn.classList.remove('active');
                likeBtn.setAttribute('aria-pressed', 'false');
                if (likes > 0) likes -= 1;
            }
            } else {
            if (dislikes > 0) dislikes -= 1;
            }
            likeCountEl.textContent = likes;
            dislikeCountEl.textContent = dislikes;
        });

        commentBtn.addEventListener('click', () => {
          const active = commentBtn.classList.toggle('active');
          commentBtn.setAttribute('aria-pressed', String(active));
          // future: open comment composer
        });

        reviewsContainer.appendChild(rev);
      });
    }


    document.getElementById('btnWatched').addEventListener('click', (e) => {
      e.currentTarget.classList.toggle('active');
    });
    document.getElementById('btnLoved').addEventListener('click', (e) => {
      e.currentTarget.classList.toggle('active');
    });
    document.getElementById('btnWatchlist').addEventListener('click', (e) => {
      e.currentTarget.classList.toggle('active');
    });

  } catch (err) {
    console.error(err);
    titleEl.textContent = 'Failed to load movie';
    overviewEl.textContent = '';
    reviewsContainer.innerHTML = `<div class="review"><p class="reviewText">Could not load details.</p></div>`;
  }
});