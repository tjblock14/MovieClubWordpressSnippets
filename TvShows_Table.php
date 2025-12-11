<?php
/*******************************************************
 * Movie Club – Consolidated Table Shortcodes (Single File)
 * - Define once, reuse for each couple via parameters.
 * - Keeps your current table structure & styling.
 *******************************************************/
/**
 * Safely implode arrays from API fields into comma-separated strings.
 * (Kept from your original code; guarded so it won't redeclare.)
 */
if (!function_exists('safe_implode')) {
    function safe_implode($field) {
        return is_array($field) ? implode(', ', $field) : $field;
    }
}

/**
 * Map a numeric rating to a background color.
 * (Return just the color string; callers handle inline style.)
 */
if (!function_exists('color_rating_cell')) {
    function color_rating_cell($rating) {
        if (!is_numeric($rating)) return '';

        if ($rating >= 9.0)                  return "#3C8D40"; // dark green
        else if ($rating >= 7.5)             return "#5F9F61"; // light green
        else if ($rating >= 6.0)             return "#A6CDA8"; // very light green
        else if ($rating >= 5.0)             return "#F3EAA3"; // yellow
        else if ($rating >= 3.5)             return "#EFB45D"; // orange
        else if ($rating >  1.0)             return "#D7572E"; // light red
        else                                 return "#AC2727"; // dark red
    }
}

/**
 * Helper: robustly read review subfields allowing for reviewer key casing differences.
 * Your API objects sometimes appear with capitalized reviewer keys for fields,
 * and lowercase for the "id". This tries several forms safely.
 */
if (!function_exists('review_value')) {
    function review_value(array $reviews, string $reviewer, string $field) {
        // Try exact, Capitalized, and lowercase reviewer keys
        $candidates = [
            $reviewer,
            ucfirst(strtolower($reviewer)),
            strtolower($reviewer),
            strtoupper($reviewer),
        ];

        foreach ($candidates as $key) {
            if (isset($reviews[$key]) && is_array($reviews[$key]) && array_key_exists($field, $reviews[$key])) {
                return $reviews[$key][$field];
            }
        }
        return '';
    }
}

/**
 * Core: register one shortcode for a couple’s table.
 *
 * @param string $shortcode   The shortcode name to register (e.g., 'tnt_table')
 * @param string $endpoint    WPGetAPI endpoint key (e.g., 'tnt_reviews')
 * @param string $reviewerA   Reviewer A key as used by your API (e.g., 'Trevor' or 'rob')
 * @param string $reviewerB   Reviewer B key as used by your API (e.g., 'Taylor' or 'terry')
 * @param string|null $labelA Optional display label for A (e.g., 'Dad'); defaults to $reviewerA
 * @param string|null $labelB Optional display label for B (e.g., 'Mom'); defaults to $reviewerB
 */
if (!function_exists('add_TvShow_table_shortcode')) 
{
    function add_TvShow_table_shortcode(string $shortcode, string $endpoint, string $reviewerA, string $reviewerB, ?string $labelA = null, ?string $labelB = null) 
	{

        add_shortcode($shortcode, function() use ($endpoint, $reviewerA, $reviewerB, $labelA, $labelB) 
		{

            // Fetch data from your configured WPGetAPI connection
            $data = wpgetapi_endpoint('movie_club_database_api', $endpoint, [
                'return' => 'body',
                'debug'  => false,
                'cache'  => false,
            ]);

            $data = json_decode($data, true);

            // Validate payload shape
            if (!is_array($data) || !isset($data['results']) || !is_array($data['results'])) {
                return '<p>Error: No data returned</p>';
            }
            $TvShows = $data['results'];

            // Normalize labels for headers (allow custom labels like "Dad"/"Mom")
            $displayA = $labelA ?? $reviewerA;
            $displayB = $labelB ?? $reviewerB;

            // ===== SORT BAR (added) =====
            $html = '
  <div class="TvShow-sortbar" style="margin:8px 0; display:flex; gap:8px; align-items:center;">
    <label for="TvShow-sort" style="font-weight:600;">Sort by:</label>
    <select id="TvShow-sort" class="TvShow-sort">
      <option value="avg_desc">Couple Average: high → low</option>
      <option value="avg_asc">Couple Average: low → high</option>
      <option value="u1_desc">' . esc_html($displayA) . ' rating — high → low</option>
      <option value="u1_asc">' . esc_html($displayA) . ' rating — low → high</option>
      <option value="u2_desc">' . esc_html($displayB) . ' rating — high → low</option>
      <option value="u2_asc">' . esc_html($displayB) . ' rating — low → high</option>
      <option value="title_az">Title — A → Z</option>
      <option value="director_az">Director — A → Z</option>
    </select>
  </div>';

            // Build table
            // (added: id and data-user1/data-user2 for sorting)
            $html .= '<div style="overflow-x: auto;">
   <table id="TvShow-table" data-user1="' . esc_attr(strtolower($reviewerA)) . '" data-user2="' . esc_attr(strtolower($reviewerB)) . '" style="border-collapse: collapse; min-width: 1400px; font-size: 14px; border: 2px solid #33151A;">
        <thead>
            <tr>
				 <th style="width: 150px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A ;">Image</th>
                <th style="width: 150px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A ;">Title</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;">Summary</th>
                <th style="width: 250px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;">Genres</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;">Premiered</th>
				<th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;">Creators</th>
				<th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;">Status</th>
				<th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;"># of Seasons</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayA) . ' Rating</th>
                <th style="width: 150px; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid #33151A;">' . esc_html($displayA) . ' Comments</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayB) . ' Rating</th>
                <th style="width: 150px; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayB) . ' Comments</th>
				<th style = "width: 150px; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayA) . ' and ' . esc_html($displayB) . ' Average</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($TvShows as $TvShow)
			{
                $TvShow_id = $TvShow['id'];
                $title    = $TvShow['title'] ?? '';

                $html .= '<tr>';

                // Title / Director / Actors / Genres
                // (added classes for sorting: title-cell, director-cell)
                $html .= '<td style="width: 250px; text-align: center; vertical-align: middle; font-size: 14px; color:#FBFCEE; border-collapse: collapse; border: 1px solid black;">'
       						. '<img src="' . esc_url($TvShow['image_url']) . '" alt="' . esc_attr($TvShow['title']) . '" ' # display the title if the show image is not loading
       						. 'style="max-width: 120px; height: auto; border-radius: 4px;" />'
       						. '</td>';
				
                $html .= '<td class="title-cell" style="width: 200px; text-align: center; vertical-align: middle; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html($title) . '</td>';
                $html .= '<td class="creators-cell" style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['creators'] ?? '')) . 	'</td>';
                #$html .= '<td style="width: 250px; text-align: center; vertical-align: middle; font-size: 14px; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['actors'] ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['genres'] 	 ?? '')) . '</td>';

                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['premiered'] 	 ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['creators'] 	 ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['status'] 	 ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['num_seasons'] 	 ?? '')) . '</td>';

                // Reviews block
                $reviews = $TvShow['reviews'] ?? [];

                // Reviewer A values
                $user1_rating = review_value($reviews, $reviewerA, 'rating');
                $user1_review = review_value($reviews, $reviewerA, 'review');
                $user1_id     = review_value($reviews, $reviewerA, 'id'); // allow for lowercase-only id keys
                $user1_color  = color_rating_cell($user1_rating);
                $user1_data_reviewer = strtolower($reviewerA); // match your JS which uses lowercase usernames

                // (fixed a tiny spacing bug: ensure a space before data-rating)
                $html .= '<td class="rating-cell" data-review-type="tv" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-TvShow-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_id) . 						   '" data-rating="' . esc_attr($user1_rating) . '" style="background-color: ' . esc_attr($user1_color) . ';">' . esc_html($user1_rating) . '</td>';

                $html .= '<td class="review-cell" data-review-type="tv" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-TvShow-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_id) . 							'">' . esc_html($user1_review) . '</td>';

                // Reviewer B values
                $user2_rating = review_value($reviews, $reviewerB, 'rating');
                $user2_review = review_value($reviews, $reviewerB, 'review');
                $user2_id     = review_value($reviews, $reviewerB, 'id');
                $user2_color  = color_rating_cell($user2_rating);
                $user2_data_reviewer = strtolower($reviewerB);

                $html .= '<td class="rating-cell" data-review-type="tv" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-TvShow-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_id) . 							'" data-rating="' . esc_attr($user2_rating) . '" style="background-color: ' . esc_attr($user2_color) . ';">' . esc_html($user2_rating) . '</td>';

                $html .= '<td class="review-cell" data-review-type="tv" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-TvShow-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_id) . 							   '">' . esc_html($user2_review) . '</td>';
				
				// Calculate average rating (only if both are numeric)
				$avg_rating = (is_numeric($user1_rating) && is_numeric($user2_rating))
    						   ? round(($user1_rating + $user2_rating) / 2, 2): '';  // keep 2 decimals
				
				// Get the color for that numeric value
				$avg_color = color_rating_cell($avg_rating);

                // (added: avg-cell class + data-rating for sorting; keep it visible)
				$html .= '<td class="avg-cell rating-cell" data-rating="' . esc_attr($avg_rating) . '" style="background-color: ' . esc_attr($avg_color) . ';">' 
      					  .	esc_html($avg_rating) . '</td>';


                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';

            // ===== SORTING SCRIPT =====
            $html .= '
<script>
(function() {
  function asNumber(v) 
  {
    if (v === null || v === undefined) return NaN;
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

      case "u2_desc" :
      case "u2_asc"  : 
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
        return text(row.querySelector(".title-cell")).toLowerCase();
      
      case "director_az":
        return text(row.querySelector(".director-cell")).toLowerCase();
      
      default:
        return "";
    }
  }

  function compareValues(a, b, numeric, asc) 
  {
    if (numeric) 
    {
      const aNaN = Number.isNaN(a), bNaN = Number.isNaN(b);
      if (aNaN && bNaN) return 0;
      if (aNaN) return 1;     // push NaN to bottom
      if (bNaN) return -1;
      return asc ? a - b : b - a;
    } 
    else 
    {
      if (a === b) return 0;
      if (asc) return a < b ? -1 : 1;
      return a > b ? -1 : 1;
    }
  }

  function sortTable(mode) 
  {
    const table = document.getElementById("TvShow-table");
    if (!table) 
        return;

    const u1 = (table.dataset.user1 || "").toLowerCase();
    const u2 = (table.dataset.user2 || "").toLowerCase();
    const tbody = table.querySelector("tbody") || table;

    const rows = Array.from(tbody.querySelectorAll("tr"));
    const indexed = rows.map((row, idx) => ({ row, idx, key: getKey(row, mode, u1, u2) }));

    const numericModes = new Set(["u1_desc","u1_asc","u2_desc","u2_asc","avg_desc","avg_asc"]);
    const asc = mode.endsWith("_asc");
    const isNumeric = numericModes.has(mode);

    indexed.sort((A, B) => 
    {
      const cmp = compareValues(A.key, B.key, isNumeric, asc);
      return cmp !== 0 ? cmp : (A.idx - B.idx); // stable
    });

    indexed.forEach(item => tbody.appendChild(item.row));
  }

  document.addEventListener("DOMContentLoaded", function() 
  {
    const sel = document.getElementById("TvShow-sort");
    if (!sel) return;

    sel.addEventListener("change", function() 
    {
      sortTable(this.value);
    });

    // Optional: default sort on load
    // sortTable(sel.value);
  });
})();
</script>';

             // ===== TVMaze CREDIT (required attribution) =====
            $html .= '<div class="tvmaze-credit" style="font-size:12px; margin-top:8px; opacity:0.7;">
               TV Show Data provided by <a href="https://www.tvmaze.com" target="_blank" rel="noopener noreferrer">TVmaze.com</a>.
            </div>';

            return $html;
        });
    }
}

/*******************************************************
 * Register your four tables using the single function.
 * If you ever add a new couple, just add one new line.
 *******************************************************/

// Trevor / Taylor (labels match names)
add_TvShow_table_shortcode('tnt_tv_show_table', 'tnt_tv_reviews', 'Trevor', 'Taylor');

// Marissa / Nathan (labels match names)
add_TvShow_table_shortcode('mn_tv_show_table', 'mn_tv_reviews', 'Marissa', 'Nathan');

// Sierra / Benett (labels match names)
add_TvShow_table_shortcode('sb_tv_show_table', 'sb_tv_reviews', 'Sierra', 'Benett');

// Dad (Rob) / Mom (Terry): custom display labels different from API reviewer keys
add_TvShow_table_shortcode('mom_dad_tv_show_table', 'mom_dad_tv_reviews', 'Rob', 'Terry', 'Dad', 'Mom');

// Mia and Logan
add_TvShow_table_shortcode('mia_logan_tv_show_table', 'mia_logan_tv_reviews', 'Mia', 'Logan');
