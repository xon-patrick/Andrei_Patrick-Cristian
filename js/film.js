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


    // helper for POST JSON form-encoded
    async function postForm(url, data) {
      const form = new URLSearchParams();
      for (const k in data) form.append(k, data[k]);
      const res = await fetch(url, { method: 'POST', body: form, credentials: 'same-origin' });
      return res.json().catch(() => ({}));
    }

    // show simple modal
    function showModal(html) {
      const overlay = document.createElement('div');
      overlay.className = 'modal-overlay';
      overlay.style = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:10000;';
      const box = document.createElement('div');
      box.className = 'modal-box';
      box.style = 'background:#111;padding:18px;border-radius:8px;max-width:720px;width:100%;color:#eee;box-shadow:0 12px 40px rgba(0,0,0,0.6);';
      box.innerHTML = html;
      overlay.appendChild(box);
      document.body.appendChild(overlay);
      return { overlay, box };
    }

    // favorite button handling
    const btnLovedEl = document.getElementById('btnLoved');
    btnLovedEl.addEventListener('click', async (e) => {
      const active = btnLovedEl.classList.toggle('active');
      // if activated, add favorite; else remove
      if (active) {
        await postForm('add_favorite.php', { tmdb_id: id, title: details.title || '', poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : '' });
      } else {
        await postForm('remove_favorite.php', { tmdb_id: id });
      }
    });

    // check and set initial favorite state
    (async () => {
      try {
        const res = await fetch('get_favorites.php', { credentials: 'same-origin' });
        if (res.ok) {
          const data = await res.json();
          const favs = data.favorites || [];
          if (favs.find(f => String(f.tmdb_id) === String(id))) btnLovedEl.classList.add('active');
        }
      } catch (e) { /* ignore */ }
    })();

    // watched button -> open modal to submit rating and review
    document.getElementById('btnWatched').addEventListener('click', (e) => {
      const html = `
        <h2 style="margin-top:0">Mark as watched</h2>
        <p style="color:#ccc">Leave a grade, review and optionally add to favorites or a list.</p>
        <div style="display:flex;gap:10px;margin:10px 0;align-items:center;">
          <label style="min-width:120px">Grade (1-10)</label>
          <select id="watched-grade">${Array.from({length:10}).map((_,i)=>`<option value="${i+1}">${i+1}</option>`).join('')}</select>
        </div>
        <div style="margin:10px 0;">
          <label>Review</label>
          <textarea id="watched-review" rows="6" style="width:100%;"></textarea>
        </div>
        <div style="display:flex;gap:12px;align-items:center;margin-top:8px;">
          <label><input type="checkbox" id="watched-fav"> Add to favorites</label>
          <button id="watched-submit" style="margin-left:auto;padding:8px 12px;background:#2b7;color:#041;border-radius:6px;border:none;">Save</button>
          <button id="watched-cancel" style="padding:8px 12px;background:#333;color:#ddd;border-radius:6px;border:none;">Cancel</button>
        </div>
      `;
      const { overlay, box } = showModal(html);
      box.querySelector('#watched-cancel').addEventListener('click', () => overlay.remove());
      box.querySelector('#watched-submit').addEventListener('click', async () => {
        const grade = box.querySelector('#watched-grade').value;
        const review = box.querySelector('#watched-review').value;
        const fav = box.querySelector('#watched-fav').checked ? 1 : 0;
        // post watched
        await postForm('add_watched.php', { tmdb_id: id, title: details.title || '', poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : '', rating: grade, liked: fav, notes: review });
        if (fav) await postForm('add_favorite.php', { tmdb_id: id, title: details.title || '', poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : '' });
        overlay.remove();
        // show small transient success
        const s = document.createElement('div'); s.textContent = 'Saved'; s.style = 'position:fixed;top:18px;left:50%;transform:translateX(-50%);background:#e6ffea;color:#044d18;padding:8px 12px;border-radius:6px;z-index:10001;'; document.body.appendChild(s);
        setTimeout(()=>s.style.opacity='0',1500); setTimeout(()=>s.remove(),2100);
      });
    });

    // watchlist -> choose list to add
    document.getElementById('btnWatchlist').addEventListener('click', async (e) => {
      // fetch user's lists
      let lists = [];
      try {
        const res = await fetch('get_lists.php', { credentials: 'same-origin' });
        if (res.ok) { const d = await res.json(); lists = d.lists || []; }
      } catch (err) { /* ignore */ }

      const options = lists.map(l => `<option value="${l.id}">${l.name}</option>`).join('');
      const html = `
        <h2 style="margin-top:0">Add to list</h2>
        <div style="margin:10px 0;">
          <select id="select-list" style="width:100%">${options}</select>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
          <button id="create-list" style="padding:8px 12px;background:#368;">New list</button>
          <button id="add-list" style="padding:8px 12px;background:#2b7;color:#041;">Add</button>
          <button id="cancel-list" style="padding:8px 12px;background:#333;color:#ddd;">Cancel</button>
        </div>
      `;
      const { overlay, box } = showModal(html);
      box.querySelector('#cancel-list').addEventListener('click', () => overlay.remove());
      box.querySelector('#create-list').addEventListener('click', () => {
        const name = prompt('List name');
        if (!name) return;
        postForm('create_list.php', { name }).then(j => {
          if (j.ok) {
            // append to select
            const sel = box.querySelector('#select-list');
            const opt = document.createElement('option'); opt.value = j.list_id; opt.text = name; sel.appendChild(opt); sel.value = j.list_id;
          } else alert('Failed to create list');
        });
      });
      box.querySelector('#add-list').addEventListener('click', async () => {
        const sel = box.querySelector('#select-list');
        const listId = sel.value;
        if (!listId) return alert('Select a list');
        const j = await postForm('add_to_list.php', { list_id: listId, tmdb_id: id, title: details.title || '', poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : '' });
        if (j.ok) {
          overlay.remove();
          const s = document.createElement('div'); s.textContent = 'Added to list'; s.style = 'position:fixed;top:18px;left:50%;transform:translateX(-50%);background:#e6ffea;color:#044d18;padding:8px 12px;border-radius:6px;z-index:10001;'; document.body.appendChild(s);
          setTimeout(()=>s.style.opacity='0',1500); setTimeout(()=>s.remove(),2100);
        } else {
          alert('Failed to add to list');
        }
      });
    });

  } catch (err) {
    console.error(err);
    titleEl.textContent = 'Failed to load movie';
    overviewEl.textContent = '';
    reviewsContainer.innerHTML = `<div class="review"><p class="reviewText">Could not load details.</p></div>`;
  }
});