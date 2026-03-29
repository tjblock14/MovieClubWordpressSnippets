(function ()
{

    /* If a token refresh interval is already running, do nothing */
    if(window.__tokenRefreshIntervalId)
    {
        return;
    }

    /*******************************************************************************
     * FUNCTION    : refreshAccessTokenOnce
     * DESCRIPTION : 
     *      This is an async function that performs one token refresh attempt.
     *******************************************************************************/
    async function refreshAccessTokenOnce()
    {
        /* Grab the current refresh token */
        const refresh = localStorage.getItem("refresh_token");

        if(!refresh)
        {
            /* There is no refresh token so there is nothing to refresh. */
            return;
        }

        /* Create the variable that will eventuallyt hold the result of our fetch operation */
        let response;

        /* Attempt to make the refresh request */
        try 
        {
            response = await fetch("https://movieclubdatabase.onrender.com/api/token/refresh/", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ refresh })
                });
        }
        catch(err)
        {
            // Network / cold start / transient issue: do NOT log out
            console.warn("⚠️ Token refresh network error (will retry later):", err);

            return;
        }

        // If backend is down or temporarily unhappy, don’t nuke tokens
        if(response.status >= 500)
        {
            console.warn(`⚠️ Token refresh server error (${response.status}). Keeping tokens; will retry.`);

            return;
        }

        if(response.ok)
        {
            const data = await response.json();

            if(data.access)
            {
                localStorage.setItem("access_token", data.access);
            }

            // IMPORTANT for rotating refresh tokens:
            if(data.refresh)
            {
                localStorage.setItem("refresh_token", data.refresh);
            }

            console.log("✅ Access token refreshed");

            return;
        }

        // Only log out on “invalid refresh token” style responses
        if(response.status === 401 || response.status === 403)
        {
            console.warn("⚠️ Refresh token invalid/expired. Logging out.");
            localStorage.removeItem("access_token");
            localStorage.removeItem("refresh_token");

            return;
        }

        // Other 4xx: don’t immediately log out; log for debugging
        const text = await response.text().catch(() => "");
        console.warn(`⚠️ Refresh failed (${response.status}). Not logging out automatically. Body:`, text);
    }

    function startTokenRefreshInterval()
    {
        // Run once shortly after load, then every 4 minutes
        refreshAccessTokenOnce();
        window.__tokenRefreshIntervalId = setInterval(refreshAccessTokenOnce, 4 * 60 * 1000);
    }

    window.addEventListener("load", startTokenRefreshInterval);
})();
