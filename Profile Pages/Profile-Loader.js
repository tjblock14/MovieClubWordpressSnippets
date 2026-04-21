document.addEventListener('DOMContentLoaded', async function ()
{
    const root = document.getElementById('mc-profile-root');
    if (!root) return;

    const loadingEl = document.getElementById('mc-profile-loading');
    const errorEl   = document.getElementById('mc-profile-error');
    const contentEl = document.getElementById('mc-profile-content');

    const urlParams = new URLSearchParams(window.location.search);
    const urlUser   = urlParams.get('user');

    const storedUsername =
        localStorage.getItem('username') ||
        localStorage.getItem('mc_username') ||
        localStorage.getItem('loggedInUser');

    const username = (urlUser || storedUsername || '').trim();

    if (!username)
    {
        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl)
        {
            errorEl.style.display = 'block';
            errorEl.textContent = 'No username found. Please log in or open a profile with ?user=username';
        }
        return;
    }

    // CHANGE THIS TO YOUR REAL BACKEND BASE URL
    const apiBase = 'https://movieclubdatabase.onrender.com/api';
    const endpoint = `${apiBase}/profile-stats/${encodeURIComponent(username)}/`;

    try
    {
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok)
        {
            throw new Error(`HTTP ${response.status}`);
        }

        const profile = await response.json();

        const setText = (id, value) =>
        {
            const el = document.getElementById(id);
            if (el) el.textContent = (value ?? '--');
        };

        const formatTitleRating = (obj) =>
        {
            if (!obj || !obj.title) return '--';
            if (obj.rating === null || obj.rating === undefined) return obj.title;
            return `${obj.title} (${obj.rating}/10)`;
        };

        const formatLatestReview = (obj) =>
        {
            if (!obj || !obj.title) return '--';
            const typeLabel =
                obj.type === 'movie' ? 'Movie' :
                obj.type === 'tv'    ? 'TV Show' :
                obj.type || '';
            return `${obj.title} — ${obj.rating}/10${typeLabel ? ` (${typeLabel})` : ''}`;
        };

        setText('mc-profile-name', profile.display_name || profile.username || username);
        setText('mc-profile-subtitle', `${profile.display_name || profile.username || username}'s Movie Club Stats`);

        const avatarEl = document.getElementById('mc-profile-avatar');
        if (avatarEl)
        {
            const name = profile.display_name || profile.username || username;
            avatarEl.textContent = name.charAt(0).toUpperCase();
        }

        setText('mc-overall-avg', profile.overall_avg);
        setText('mc-total-reviews', profile.total_reviews);
        setText('mc-movie-avg', profile.movie_avg);
        setText('mc-tv-avg', profile.tv_avg);
        setText('mc-current-streak', profile.current_streak);
        setText('mc-longest-streak', profile.longest_streak);
        setText('mc-perfect-tens', profile.perfect_tens);

        setText('mc-current-streak-2', profile.current_streak);
        setText('mc-longest-streak-2', profile.longest_streak);
        setText('mc-current-movie-streak', profile.current_movie_streak);
        setText('mc-longest-movie-streak', profile.longest_movie_streak);
        setText('mc-current-tv-streak', profile.current_tv_streak);
        setText('mc-longest-tv-streak', profile.longest_tv_streak);

        setText('mc-favorite-genre', profile.favorite_genre || '--');
        setText('mc-highest-movie', formatTitleRating(profile.highest_movie));
        setText('mc-highest-show', formatTitleRating(profile.highest_show));
        setText('mc-latest-review', formatLatestReview(profile.latest_review));

        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';
    }
    catch (err)
    {
        console.error('Profile load failed:', err);

        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl)
        {
            errorEl.style.display = 'block';
            errorEl.textContent = `Unable to load profile data for "${username}".`;
        }
    }
});