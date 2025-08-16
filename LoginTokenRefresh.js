// This function will refresh a logged in users token so that they don't automatically get logged out after a few minutes
function startTokenRefreshInterval() {
  // Check the local storage for the refresh token of the user
  const refreshToken = localStorage.getItem('refresh_token');
  if (!refreshToken) return; // Only run if user is logged in

  setInterval(async () => {
    try {
      const response = await fetch('https://movieclubdatabase.onrender.com/api/token/refresh/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh: refreshToken })
      });

      if (response.ok) {
        const data = await response.json();
        localStorage.setItem('access_token', data.access);
        console.log("✅ Access token refreshed");
      } else {
        console.warn("⚠️ Refresh failed. Logging out.");
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        // Optional: redirect to login
        // window.location.href = "/login";
      }
    } catch (error) {
      console.error("Error refreshing token:", error);
    }
  }, 4 * 60 * 1000); // Every 4 minutes
}

// Start it on every page load
window.addEventListener('load', () => {
  startTokenRefreshInterval();
});
