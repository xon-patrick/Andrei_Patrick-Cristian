
import { posters } from './api.js';

function createCardElement(movie, details = {}) {
    const template = document.getElementById('cardTemplate');
    const node = template.content.firstElementChild.cloneNode(true);

    const img = node.querySelector('.card-backdrop');
    const titleEl = node.querySelector('.card-title');
    const ratingEl = node.querySelector('.rating');
    const directorEl = node.querySelector('.director');
    const durationEl = node.querySelector('.duration');

    const backdropPath = movie.backdrop_path || movie.poster_path;
    img.src = posters.backdrop(backdropPath) || posters.poster(movie.poster_path) || '';
    img.alt = movie.title;

    titleEl.textContent = movie.title;
    ratingEl.textContent = movie.vote_average ? `${movie.vote_average.toFixed(1)}/10` : '—';

    const runtime = details.runtime ? `${details.runtime}m` : (movie.runtime ? `${movie.runtime}m` : '—');
    durationEl.textContent = runtime;

    let director = 'Unknown';
    if (details.credits && Array.isArray(details.credits.crew)) {
        const dir = details.credits.crew.find(c => c.job === 'Director' || c.department === 'Directing');
        director = dir ? dir.name : director;
    }
    directorEl.textContent = director;

    node.dataset.movieId = movie.id;

    return node;
}

export function renderMovies(container, moviesWithDetails) {
    container.innerHTML = '';
    if (!moviesWithDetails || moviesWithDetails.length === 0) {
        container.innerHTML = `<p style="color:var(--muted)">No results.</p>`;
        return;
    }
    const fragment = document.createDocumentFragment();
    moviesWithDetails.forEach(item => {
        const el = createCardElement(item.movie, item.details || {});
        fragment.appendChild(el);
    });
    container.appendChild(fragment);
}

export function showPlaceholders(container, count = 8) {
    container.innerHTML = '';
    for (let i = 0; i < count; i++) {
        const div = document.createElement('div');
        div.className = 'card';
        div.innerHTML = `<div class="card-media placeholder"></div><div class="card-body"><div style="height:18px;background:linear-gradient(90deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));border-radius:6px;"></div><div style="height:12px;width:60%;background:rgba(255,255,255,0.02);border-radius:6px;margin-top:8px;"></div></div>`;
        container.appendChild(div);
    }
}