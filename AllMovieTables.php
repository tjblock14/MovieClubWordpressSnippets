<?php
/*******************************************************
 * Movie Club – Consolidated Table Shortcodes (Single File)
 * - Define once, reuse for each couple via parameters.
 * - Adds fixed-height scroll wrapper (≈5 rows) so horizontal scrollbar is always reachable.
 * - Sticky header inside the scroll container.
 * - Unique IDs per shortcode instance (prevents sort conflicts).
 *******************************************************/

/**
 * Safely implode arrays from API fields into comma-separated strings.
 * (Kept from your original code; guarded so it won't redeclare.)
 */
if (!function_exists('safe_implode')) 
{
    function safe_implode($field) 
    {
        return is_array($field) ? implode(', ', $field) : $field;
    }
}

/**
 * Map a numeric rating to a background color.
 * (Return just the color string; callers handle inline style.)
 */
if (!function_exists('color_rating_cell')) 
{
    function color_rating_cell($rating) 
    {
        if(!is_numeric($rating)) 
        {
            return '';
        }

        if($rating >= 9.5)
        {
            return "#0096FF"; // Blue
        }
        else if($rating >= 8.5)
        {
            return "#3C8D40"; // dark green
        }
        else if ($rating >= 7.0)
        {
            return "#5F9F61"; // light green
        }
        else if($rating >= 5.0)
        {
            return "#F3EAA3"; // yellow
        }
        else if($rating >= 3.5)
        {
            return "#EFB45D"; // orange
        }
        else
        {
            return "#AC2727"; // dark red
        }
    }
}

/**
 * Helper: robustly read review subfields allowing for reviewer key casing differences.
 */
if (!function_exists('review_value'))
{
    function review_value(array $reviews, string $reviewer, string $field) 
    {
        $candidates = 
        [
            $reviewer,
            ucfirst(strtolower($reviewer)),
            strtolower($reviewer),
            strtoupper($reviewer),
        ];

        foreach ($candidates as $key) 
        {
            if (isset($reviews[$key]) && is_array($reviews[$key]) && array_key_exists($field, $reviews[$key])) 
            {
                return $reviews[$key][$field];
            }
        }

        return '';
    }
}

/**
 * Core: register one shortcode for a couple’s table.
 */
if (!function_exists('add_movie_table_shortcode'))
{
    function add_movie_table_shortcode(string $shortcode, string $endpoint, string $reviewerA, string $reviewerB, ?string $labelA = null, ?string $labelB = null)
    {
        add_shortcode($shortcode, function() use ($shortcode, $endpoint, $reviewerA, $reviewerB, $labelA, $labelB)
        {
            // Fetch data from the database via the endpoint configured in our WpGetAPI extension on WordPress
            $data = wpgetapi_endpoint('movie_club_database_api', $endpoint, 
                    [
                        'return' => 'body',
                        'debug'  => false,
                        'cache'  => false,
                    ]);

            $data = json_decode($data, true);

            // Validate payload shape
            if (!is_array($data) || !isset($data['results']) || !is_array($data['results']))
            {
                return '<p> Error: No data returned </p>';
            }

            // Grab only the "results" section of the data which is all of the movies in the database
            $movies = $data['results'];

            // Normalize labels for headers (allow custom labels like "Dad"/"Mom")
            $displayA = $labelA ?? $reviewerA;
            $displayB = $labelB ?? $reviewerB;

            // ✅ UNIQUE IDs so multiple tables on different pages (or same page) don't conflict
            $uid      = 'mc_' . sanitize_key($shortcode) . '_' . sanitize_key($endpoint) . '_' . wp_rand(1000, 9999);
            $sort_id  = $uid . '_sort';
            $table_id = $uid . '_table';

            /****************************************************************************
             * This chunk creates the sort bar and all the valid sort options.
             ****************************************************************************/
            $html = '
                <div class = "movie-sortbar" style = "margin : 8px 0; display : flex; gap : 8px; align-items : center;" >
                    <label for = "' . esc_attr($sort_id) . '" style = "font-weight:600;" > 
                        Sort by:
                    </label>
                    <select
                        id="' . esc_attr($sort_id) . '" class = "movie-sort">
                        <option
                            value = "avg_desc" > Couple Average: high → low
                        </option>
                        <option
                            value = "avg_asc" > Couple Average: low → high
                        </option>
                        <option
                            value  = "u1_desc" > ' . esc_html($displayA) . ' rating — high → low
                        </option>
                        <option
                            value = "u1_asc" > ' . esc_html($displayA) . ' rating — low → high
                        </option>
                        <option
                            value = "u2_desc" > ' . esc_html($displayB) . ' rating — high → low
                        </option>
                        <option
                            value = "u2_asc" > ' . esc_html($displayB) . ' rating — low → high
                        </option>
                        <option
                            value = "title_az" > Title — A → Z
                        </option>
                        <option
                            value = "director_az" > Director — A → Z
                        </option>
                    </select>
                </div>';

            /****************************************************************************
             * This chunk creates the first row of the table with the title of each row.
             ****************************************************************************/
            $html .= '
                <div class = "mc-table-scroll-wrapper">
                <table 
                    id = "' . esc_attr($table_id) . '" 
                    data-user1 = "' . esc_attr(strtolower($reviewerA)) . '"
                    data-user2 = "' . esc_attr(strtolower($reviewerB)) . '" 
                    style = "border-collapse: collapse; min-width: 1400px; font-size: 14px;" >
                <thead>
                    <tr>
                        <th
                            class = "table-title-cells-style" > Title
                        </th>
                        <th
                            class = "table-title-cells-style summary-column-width" > Summary
                        </th>
                        <th 
                            class = "table-title-cells-style short-info-column-width" > Director
                        </th>
                        <th
                            class = "table-title-cells-style actors-column-width" > Actors
                        </th>
                        <th
                            class = "table-title-cells-style short-info-column-width" > Genres
                        </th>
                        <th
                            class = "table-title-cells-style short-info-column-width" > Release Year
                        </th>
                        <th
                            class = "table-title-cells-style short-info-column-width" > Runtime
                            </th>
                        <th
                            class = "table-title-cells-style" > ' . esc_html($displayA) . ' Rating
                        </th>
                        <th
                            class = "table-title-cells-style" > ' . esc_html($displayB) . ' Rating
                        </th>
                        <th
                            class = "table-title-cells-style" > ' . esc_html($displayA) . ' and ' . esc_html($displayB) . ' Average
                        </th>
                    </tr>
                </thead>

                <tbody>';

            /*****************************************************************************************
             * This is where the row for each and every movie in the database is created in the 
             * couple's review table. The foreach statement loops through all of the movies it grabbed
             * from the database and grabs the appropriate information along with the reveiws by each
             * user that is apart of the couple currently being viewed
             *****************************************************************************************/
            foreach ($movies as $movie)
            {
                $movie_id = $movie['movie_id'];
                $title    = $movie['title'] ?? '';

                $release_yr = $movie['release_yr'];
                $runtime    = $movie['runtime'];
                $summary    = $movie['summary'];

                $html .= '<tr>';

                $html .= '
                    <td 
                        class = "table-title-cells-style" >
                        <div
                            class = "movie-poster-tooltip" data-title = "' . esc_attr($movie['title']) . '" >
                            <img 
                                src = "' . esc_url($movie['poster_url']) . '" 
                                alt = "' . esc_attr($movie['title']) . '" 
                                style = "max-width:120px;height:auto;border-radius:4px;" 
                            />
                        </div>
                    </td>';

                // This is the general movie information such as title, the director, the cast, etc. that we store in our database
                $html .= '<td class = "tables-small-data-style" > ' . esc_html($summary) . ' </td>';
                $html .= '<td class = "tables-small-data-style" > ' . esc_html(safe_implode($movie['director'] ?? '')) . ' </td>';
                $html .= '<td class = "tables-small-data-style" > ' . esc_html(safe_implode($movie['actors'] ?? '')) . ' </td>';
                $html .= '<td class = "tables-small-data-style" > ' . esc_html(safe_implode($movie['genres'] ?? '')) . ' </td>';
                $html .= '<td class = "tables-small-data-style" > ' . esc_html($release_yr) . ' </td>';
                $html .= '<td class = "tables-small-data-style" > ' . esc_html($runtime) . ' </td>';

                // Access all of the stored reviews for the movie we are currently getting info for
                $reviews = $movie['reviews'] ?? [];

                // Get all relevant review information for user 1 of this couple
                $user1_rating = review_value($reviews, $reviewerA, 'rating');
                $user1_review = review_value($reviews, $reviewerA, 'review');
                $user1_id     = review_value($reviews, $reviewerA, 'id');
                $user1_color  = color_rating_cell($user1_rating);
                $user1_data_reviewer = strtolower($reviewerA);

                // Add this information to the next column of this row
                $html .= '
                    <td
                        class = "rating-cell tables-small-data-style" 
                        data-review-type = "movie" 
                        data-reviewer = "' . esc_attr($user1_data_reviewer) . '"
                        data-id = "' . esc_attr($movie_id) . '"
                        data-movie-title = "' . esc_attr($title) . '"
                        data-review-id = "' . esc_attr($user1_id) . '"
                        data-rating = "' . esc_attr($user1_rating) . '"
                        data-review = "' . esc_attr($user1_review) . '" 
                        style = "background-color: ' . esc_attr($user1_color) . ';" > ' . esc_html($user1_rating) . ' 
                    </td>';

                // Now, get all relevant review information for user 2 of this couple
                $user2_rating = review_value($reviews, $reviewerB, 'rating');
                $user2_review = review_value($reviews, $reviewerB, 'review');
                $user2_id     = review_value($reviews, $reviewerB, 'id');
                $user2_color  = color_rating_cell($user2_rating);
                $user2_data_reviewer = strtolower($reviewerB);

                // Add this information to the next column of this row
                $html .= '
                    <td 
                        class = "rating-cell tables-small-data-style" 
                        data-review-type = "movie"
                        data-reviewer = "' . esc_attr($user2_data_reviewer) . '" 
                        data-id = "' . esc_attr($movie_id) . '"
                        data-movie-title = "' . esc_attr($title) . '"
                        data-review-id = "' . esc_attr($user2_id) . '"
                        data-rating="' . esc_attr($user2_rating) . '"
                        data-review = "' . esc_attr($user2_review) . '"
                        style = "background-color: ' . esc_attr($user2_color) . ';">' . esc_html($user2_rating) . '
                    </td>';

                // Calculate the average rating between the two users if they both have valid ratings
                $avg_rating = (is_numeric($user1_rating) && is_numeric($user2_rating))
                                ? round(($user1_rating + $user2_rating) / 2, 2)
                                : '';

                // Find what the color of this cell should be based on its rating amd then add this information to the next column of this row
                $avg_color = color_rating_cell($avg_rating);

                $html .= '
                    <td 
                        class = "avg-cell rating-cell tables-small-data-style"
                        data-rating = "' . esc_attr($avg_rating) . '"
                        style = "background-color: ' . esc_attr($avg_color) . ';" > ' . esc_html($avg_rating) . ' 
                    </td>';

                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';

            // ===== SORTING SCRIPT (updated to use unique IDs) =====
            $html .= '
            <script>
                (function() 
                {
                    function asNumber(v) 
                    {
                        if(v === null || v === undefined)
                        {
                            return NaN;
                        }
                        
                        const n = parseFloat(String(v).replace(",", "."));
                        
                        return Number.isFinite(n) ? n : NaN;
                    }

                    function text(el)
                    {
                        return (el && el.textContent || "").trim();
                    }

                    function getReviewerCell(row, reviewer)
                    {
                        return row.querySelector(\'.rating-cell[data-reviewer="\' + reviewer + \'"]\');
                    }

                    function getAvgCell(row)
                    {
                        return row.querySelector(".avg-cell");
                    }

                    function getKey(row, mode, u1, u2) 
                    {
                        switch (mode) 
                        {
                            case "u1_desc":
                            case "u1_asc": 
                            {
                                const c = getReviewerCell(row, u1);
                                return asNumber(c ? c.dataset.rating : NaN);
                            }

                            case "u2_desc":
                            case "u2_asc": 
                            {
                                const c = getReviewerCell(row, u2);
                                return asNumber(c ? c.dataset.rating : NaN);
                            }

                            case "avg_desc":
                            case "avg_asc": 
                            {
                                const c = getAvgCell(row);
                                return asNumber(c ? c.dataset.rating : NaN);
                            }
                            
                            case "title_az":
                            {
                                return text(row.querySelector(".title-cell")).toLowerCase();
                            }
                            
                            case "director_az":
                            {
                                return text(row.querySelector(".director-cell")).toLowerCase();
                            }
                            default:
                            {
                                return "";
                            }
                        }
                    }

                    function compareValues(a, b, numeric, asc)
                    {
                        if (numeric)
                        {
                            const aNaN = Number.isNaN(a), bNaN = Number.isNaN(b);
                            if(aNaN && bNaN)
                            {
                                return 0;
                            }

                            if(aNaN)
                            {
                                return 1;
                            }
                            if(bNaN)
                            {
                                return -1;
                            }
                            
                            return asc ? a - b : b - a;
                        }
                        else
                        {
                            if(a === b)
                            {
                                return 0;
                            }
                            if(asc)
                            {
                                return a < b ? -1 : 1;
                            }

                            return a > b ? -1 : 1;
                        }
                    }

                    function sortTable(mode, tableId)
                    {
                        const table = document.getElementById(tableId);
                        
                        if(!table)
                        {
                            return;
                        }

                        const u1    = (table.dataset.user1 || "").toLowerCase();
                        const u2    = (table.dataset.user2 || "").toLowerCase();
                        const tbody = table.querySelector("tbody") || table;

                        const rows    = Array.from(tbody.querySelectorAll("tr"));
                        const indexed = rows.map((row, idx) => ({ row, idx, key: getKey(row, mode, u1, u2) }));

                        const numericModes = new Set(["u1_desc","u1_asc","u2_desc","u2_asc","avg_desc","avg_asc"]);
                        const asc          = mode.endsWith("_asc");
                        const isNumeric    = numericModes.has(mode);

                        indexed.sort((A, B) => 
                        {
                            const cmp = compareValues(A.key, B.key, isNumeric, asc);
                            return cmp !== 0 ? cmp : (A.idx - B.idx);
                        });

                        indexed.forEach(item => tbody.appendChild(item.row));
                    }

                    document.addEventListener("DOMContentLoaded", function()
                    {
                        const sel = document.getElementById("' . esc_js($sort_id) . '");
                        
                        if(!sel)
                        {
                            return;
                        }

                        sel.addEventListener("change", function() 
                        {
                            sortTable(this.value, "' . esc_js($table_id) . '");
                        });
                    });
                })();
            </script>';

            /* TMDB attribution placed just under the table */
            $html .= '
                <div
                    class = "tmdb-attribution" 
                    style = "
                        display        : flex; 
                        flex-direction : column;
                        align-items    : center;
                        gap            : 6px;
                        margin-top     : 10px;
                        font-size      : 12px;
                        color          : #aaa;
                        text-align     : center;
                    " >
                    <img 
                        src     = "https://www.themoviedb.org/assets/2/v4/logos/v2/blue_long_1-8ba2ac31f354005783fab473602c34c3f4fd207150182061e425d366e4f34596.svg"
                        alt     = "The Movie Database (TMDB)"
                        style   = "height:10px;"
                        loading = "lazy"
                    />
                    <span>
                        The Movie portion of TnT Movie Club uses the TMDB API but is not endorsed or certified by TMDB.
                    </span>
                </div>';

            return $html;
        });
    }
}

/*******************************************************
* Create the shortcodes for each couple here.
*******************************************************/

// Trevor / Taylor
add_movie_table_shortcode('tnt_table', 'tnt_reviews', 'Trevor', 'Taylor');

// Marissa / Nathan
add_movie_table_shortcode('mn_table', 'mn_reviews', 'Marissa', 'Nathan');

// Sierra / Benett
add_movie_table_shortcode('sb_table', 'sb_reviews', 'Sierra', 'Benett');

// Dad (Rob) / Mom (Terry)
add_movie_table_shortcode('mom_dad_table', 'mom_dad_reviews', 'Rob', 'Terry', 'Dad', 'Mom');

// Mia and Logan
add_movie_table_shortcode('mia_logan_table', 'mia_logan_reviews', 'Mia', 'Logan');

// Annie and Felix
add_movie_table_shortcode('af_movie_table', 'af_reviews', 'Annie', 'Felix');
