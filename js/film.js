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
  const tmdbRatingContainer = document.getElementById('tmdbRatingContainer');

  // loading state
  titleEl.textContent = 'Loading…';
  overviewEl.textContent = '';

  try {
    const res = await fetch(`${BASE_URL}/movie/${id}?api_key=${API_KEY}&language=en-US&append_to_response=credits,reviews,images`);
    if (!res.ok) throw new Error('Failed to fetch movie details');
    const details = await res.json();
    
    console.log('Movie details loaded:', details);

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

    // Display TMDB Rating in the dedicated TMDB section (no text reviews)
    const rating10 = details.vote_average ? details.vote_average.toFixed(1) : 'N/A';
    const rating5 = details.vote_average ? Math.round(details.vote_average / 2) : 0;
    const starsHtml = Array.from({ length: 5 })
      .map((_, i) => `<span class="star ${i < rating5 ? 'filled' : ''}">★</span>`)
      .join('');

    const votesCount = details.vote_count ? `(${details.vote_count.toLocaleString()} votes)` : '';

    tmdbRatingContainer.innerHTML = `
      <div style="display: flex; align-items: center; gap: 20px;">
        <div style="flex: 1;">
          <div style="color: #ffb86b; font-weight: 600; font-size: 1.2rem; margin-bottom: 8px;">${rating10}/10</div>
          <div style="color: #ffd166;">${starsHtml}</div>
          <div style="color: #999; font-size: 0.9rem; margin-top: 8px;">${votesCount}</div>
        </div>
      </div>
    `;

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

    // Load site reviews from database (not TMDB)
    reviewsContainer.innerHTML = '';
    
    // Helper to load site reviews
    async function loadSiteReviews() {
      try {
        // Fetch reviews using tmdb_id
        const reviewsRes = await fetch('get_reviews.php?tmdb_id=' + id, { credentials: 'same-origin' });
        
        if (!reviewsRes.ok) {
          reviewsContainer.innerHTML = '<p style="color: #999;">No reviews yet. Be the first to review!</p>';
          return;
        }
        
        const reviewsData = await reviewsRes.json();
        const siteReviews = reviewsData.reviews || [];
        
        if (siteReviews.length === 0) {
          reviewsContainer.innerHTML = '<p style="color: #999;">No reviews yet. Be the first to review!</p>';
          return;
        }
        
        // Calculate average grade
        const totalGrade = siteReviews.reduce((sum, review) => sum + parseInt(review.grade || 0), 0);
        const avgGrade = (totalGrade / siteReviews.length).toFixed(1);
        const avgGradeOutOf5 = Math.round(avgGrade / 2);
        const avgStarsHtml = Array.from({ length: 5 })
          .map((_, i) => `<span class="star ${i < avgGradeOutOf5 ? 'filled' : ''}">★</span>`)
          .join('');
        
        // Display average at the top
        reviewsContainer.innerHTML = `
          <div style="background: #222; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #ffb86b;">
            <div style="display: flex; align-items: center; gap: 20px;">
              <div style="flex: 1;">
                <div style="color: #999; font-size: 0.9rem; margin-bottom: 4px;">Average Journal Grade</div>
                <div style="color: #ffb86b; font-weight: 600; font-size: 1.5rem; margin-bottom: 8px;">${avgGrade}/10</div>
                <div style="color: #ffd166; font-size: 1.2rem;">${avgStarsHtml}</div>
                <div style="color: #999; font-size: 0.9rem; margin-top: 8px;">${siteReviews.length} review${siteReviews.length !== 1 ? 's' : ''}</div>
              </div>
            </div>
          </div>
        `;
        
        // Display individual site reviews
        siteReviews.forEach(review => {
          const reviewEl = document.createElement('div');
          reviewEl.className = 'review';
          reviewEl.style = 'background: #1a1a1a; padding: 16px; border-radius: 6px; margin-bottom: 12px; border-left: 3px solid #ffb86b;';
          
          const grade = review.grade ? parseInt(review.grade) : 0;
          const gradeOutOf5 = Math.round(grade / 2);
          const starsHtml = Array.from({ length: 5 })
            .map((_, i) => `<span class="star ${i < gradeOutOf5 ? 'filled' : ''}">★</span>`)
            .join('');
          
          const dateStr = new Date(review.created_at).toLocaleDateString();
          
          reviewEl.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
              <div>
                <strong style="color: #fff; font-size: 1.1rem;">${review.username || 'Anonymous'}</strong>
                <div style="color: #999; font-size: 0.9rem; margin-top: 4px;">${dateStr}</div>
              </div>
              <div style="text-align: right;">
                <div style="color: #ffb86b; font-weight: 600; font-size: 1.2rem; margin-bottom: 4px;">${grade}/10</div>
                <div style="color: #ffd166;">${starsHtml}</div>
              </div>
            </div>
            ${review.review_text ? `<p style="color: #ddd; line-height: 1.5; margin-top: 10px;">${escapeHtml(review.review_text)}</p>` : ''}
          `;
          
          reviewsContainer.appendChild(reviewEl);
        });
      } catch (err) {
        console.error('Error loading site reviews:', err);
        reviewsContainer.innerHTML = '<p style="color: #999;">Could not load reviews.</p>';
      }
    }
        
    // Load site reviews
    await loadSiteReviews();
    // helper for POST JSON form-encoded
    async function postForm(url, data) {
      const form = new URLSearchParams();
      for (const k in data) form.append(k, data[k]);
      const res = await fetch(url, { method: 'POST', body: form, credentials: 'same-origin' });
      return res.json().catch(() => ({}));
    }

    // escape HTML to prevent XSS
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
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

    // Check if user has already watched this film
    let userReview = null;
    try {
      const res = await fetch('get_user_review.php?tmdb_id=' + id, { credentials: 'same-origin' });
      if (res.ok) {
        const data = await res.json();
        if (data.watched) {
          userReview = data;
        }
      }
    } catch (e) { /* ignore */ }

    // watched button -> open modal to submit rating and review (or edit existing)
    document.getElementById('btnWatched').addEventListener('click', (e) => {
      // Get today's date or existing watched date
      const defaultDate = userReview && userReview.watched_at ? userReview.watched_at.split(' ')[0] : new Date().toISOString().split('T')[0];
      const defaultGrade = userReview ? userReview.grade : 5;
      const defaultReview = userReview ? userReview.review_text || '' : '';
      const isEdit = userReview !== null;
      
      const html = `
        <h2 style="margin-top:0">${isEdit ? 'Edit Review' : 'Mark as watched'}</h2>
        <p style="color:#ccc">${isEdit ? 'Update your review and grade.' : 'Leave a grade, review and optionally add to favorites.'}</p>
        <div style="display:flex;gap:10px;margin:10px 0;align-items:center;">
          <label style="min-width:120px">Watched on</label>
          <input type="date" id="watched-date" value="${defaultDate}" style="padding: 6px; background: #0a0a0a; color: #fff; border: 1px solid #333; border-radius: 4px;">
        </div>
        <div style="display:flex;gap:10px;margin:10px 0;align-items:center;">
          <label style="min-width:120px">Grade (1-10)</label>
          <select id="watched-grade" style="padding: 6px;">${Array.from({length:10}).map((_,i)=>`<option value="${i+1}" ${i+1 === defaultGrade ? 'selected' : ''}>${i+1}</option>`).join('')}</select>
        </div>
        <div style="margin:10px 0;">
          <label>Review</label>
          <textarea id="watched-review" rows="6" style="width:100%; padding: 8px; border-radius: 4px; border: 1px solid #333; background: #0a0a0a; color: #fff;">${escapeHtml(defaultReview)}</textarea>
        </div>
        <div style="display:flex;gap:12px;align-items:center;margin-top:8px;">
          <label><input type="checkbox" id="watched-fav"> Add to favorites</label>
          ${isEdit ? '<button id="watched-delete" style="padding:8px 12px;background:#c33;color:#fff;border-radius:6px;border:none;cursor:pointer;">Delete</button>' : ''}
          <button id="watched-submit" style="margin-left:auto;padding:8px 12px;background:#2b7;color:#041;border-radius:6px;border:none;cursor:pointer;">${isEdit ? 'Update' : 'Save'}</button>
          <button id="watched-cancel" style="padding:8px 12px;background:#333;color:#ddd;border-radius:6px;border:none;cursor:pointer;">Cancel</button>
        </div>
      `;
      const { overlay, box } = showModal(html);
      box.querySelector('#watched-cancel').addEventListener('click', () => overlay.remove());
      
      // Add delete button handler if editing
      if (isEdit) {
        box.querySelector('#watched-delete').addEventListener('click', async () => {
          if (!confirm('Are you sure you want to delete this review? This will remove it from your watched list and recent activity.')) {
            return;
          }
          
          const deleteRes = await postForm('delete_review.php', { tmdb_id: id });
          
          if (deleteRes.error) {
            alert('Error deleting review: ' + (deleteRes.message || deleteRes.error));
            return;
          }
          
          overlay.remove();
          
          // Show success message
          const s = document.createElement('div');
          s.textContent = 'Review deleted!';
          s.style = 'position:fixed;top:18px;left:50%;transform:translateX(-50%);background:#ffe6e6;color:#8b0000;padding:8px 12px;border-radius:6px;z-index:10001;';
          document.body.appendChild(s);
          setTimeout(() => s.style.opacity = '0', 1500);
          setTimeout(() => s.remove(), 2100);
          
          // Reset userReview and reload reviews
          userReview = null;
          btnWatched.textContent = 'Watched';
          btnWatched.style.background = '';
          if (btnRewatch) btnRewatch.remove();
          loadSiteReviews();
        });
      }
      
      box.querySelector('#watched-submit').addEventListener('click', async () => {
        const watchedDate = box.querySelector('#watched-date').value;
        const grade = box.querySelector('#watched-grade').value;
        const review = box.querySelector('#watched-review').value;
        const fav = box.querySelector('#watched-fav').checked ? 1 : 0;
        
        // Save to database via save_review.php (editing mode - is_rewatch: 0)
        const saveRes = await postForm('save_review.php', {
          tmdb_id: id,
          title: details.title || '',
          poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : '',
          grade: grade,
          review_text: review,
          rating: grade,
          liked: fav,
          notes: review,
          watched_date: watchedDate,
          is_rewatch: '0'
        });
        
        console.log('Save response:', saveRes);
        
        if (saveRes.error) {
          alert('Error saving review: ' + (saveRes.message || saveRes.error));
          return;
        }
        
        if (fav) {
          await postForm('add_favorite.php', {
            tmdb_id: id,
            title: details.title || '',
            poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : ''
          });
        }
        
        overlay.remove();
        
        // Show success message
        const s = document.createElement('div');
        s.textContent = isEdit ? 'Review updated!' : 'Review saved!';
        s.style = 'position:fixed;top:18px;left:50%;transform:translateX(-50%);background:#e6ffea;color:#044d18;padding:8px 12px;border-radius:6px;z-index:10001;';
        document.body.appendChild(s);
        setTimeout(() => s.style.opacity = '0', 1500);
        setTimeout(() => s.remove(), 2100);
        
        // Update userReview and reload reviews
        userReview = { watched: true, grade: grade, review_text: review, watched_at: watchedDate };
        loadSiteReviews();
      });
    });

    // Add rewatch button next to watched button
    const btnWatched = document.getElementById('btnWatched');
    let btnRewatch = null;
    if (userReview) {
      btnWatched.textContent = 'Edit Review';
      btnWatched.style.background = '#456';
      
      // Create rewatch button
      btnRewatch = document.createElement('button');
      btnRewatch.className = 'iconBtn';
      btnRewatch.textContent = 'Rewatch';
      btnRewatch.style = 'background: #2b7; color: #041; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 1rem; margin-left: 8px;';
      btnWatched.parentNode.insertBefore(btnRewatch, btnWatched.nextSibling);
      
      btnRewatch.addEventListener('click', () => {
        const today = new Date().toISOString().split('T')[0];
        
        const html = `
          <h2 style="margin-top:0">Rewatch Movie</h2>
          <p style="color:#ccc">Add a new review for this rewatch.</p>
          <div style="display:flex;gap:10px;margin:10px 0;align-items:center;">
            <label style="min-width:120px">Watched on</label>
            <input type="date" id="watched-date" value="${today}" style="padding: 6px; background: #0a0a0a; color: #fff; border: 1px solid #333; border-radius: 4px;">
          </div>
          <div style="display:flex;gap:10px;margin:10px 0;align-items:center;">
            <label style="min-width:120px">Grade (1-10)</label>
            <select id="watched-grade" style="padding: 6px;">${Array.from({length:10}).map((_,i)=>`<option value="${i+1}" ${i+1 === 5 ? 'selected' : ''}>${i+1}</option>`).join('')}</select>
          </div>
          <div style="margin:10px 0;">
            <label>Review</label>
            <textarea id="watched-review" rows="6" style="width:100%; padding: 8px; border-radius: 4px; border: 1px solid #333; background: #0a0a0a; color: #fff;"></textarea>
          </div>
          <div style="display:flex;gap:12px;align-items:center;margin-top:8px;">
            <button id="watched-submit" style="margin-left:auto;padding:8px 12px;background:#2b7;color:#041;border-radius:6px;border:none;cursor:pointer;">Save Rewatch</button>
            <button id="watched-cancel" style="padding:8px 12px;background:#333;color:#ddd;border-radius:6px;border:none;cursor:pointer;">Cancel</button>
          </div>
        `;
        const { overlay, box } = showModal(html);
        box.querySelector('#watched-cancel').addEventListener('click', () => overlay.remove());
        box.querySelector('#watched-submit').addEventListener('click', async () => {
          const watchedDate = box.querySelector('#watched-date').value;
          const grade = box.querySelector('#watched-grade').value;
          const review = box.querySelector('#watched-review').value;
          
          // Save to database via save_review.php (rewatch mode - is_rewatch: 1)
          const saveRes = await postForm('save_review.php', {
            tmdb_id: id,
            title: details.title || '',
            poster: details.poster_path ? `${IMG_BASE}w342${details.poster_path}` : '',
            grade: grade,
            review_text: review,
            rating: grade,
            liked: 0,
            notes: review,
            watched_date: watchedDate,
            is_rewatch: '1'
          });
          
          if (saveRes.error) {
            alert('Error saving rewatch: ' + (saveRes.message || saveRes.error));
            return;
          }
          
          overlay.remove();
          
          // Show success message
          const s = document.createElement('div');
          s.textContent = 'Rewatch saved!';
          s.style = 'position:fixed;top:18px;left:50%;transform:translateX(-50%);background:#e6ffea;color:#044d18;padding:8px 12px;border-radius:6px;z-index:10001;';
          document.body.appendChild(s);
          setTimeout(() => s.style.opacity = '0', 1500);
          setTimeout(() => s.remove(), 2100);
          
          // Reload reviews
          loadSiteReviews();
        });
      });
    }

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
    console.error('Film loading error:', err);
    titleEl.textContent = 'Failed to load movie';
    overviewEl.textContent = 'Error: ' + err.message;
    reviewsContainer.innerHTML = `<div class="review"><p class="reviewText">Could not load details.</p></div>`;
  }
});