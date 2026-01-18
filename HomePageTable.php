<?php
/*******************************************************
 * Club Average table (homepage)
 * Columns: Title | Director | Actors | Genres | Club Average | # Reviews
 * - Adds fixed-height scroll wrapper (so horizontal scrollbar is always reachable)
 * - Sticky header with solid background + persistent bottom divider line
 * - Prevents table from overlaying site dropdown menus (isolated stacking context)
 *******************************************************/
if (!function_exists('club_safe_implode')) {
    function club_safe_implode($field) {
        return is_array($field) ? implode(', ', $field) : ($field ?? '');
    }
}

add_shortcode('club_average_table', function() {

    // Call your DRF averages endpoint configured in WPGetAPI as 'club_average'
    $resp = wpgetapi_endpoint('movie_club_database_api', 'club_average', [
        'return' => 'body',
        'debug'  => false,
        'cache'  => false,
        'query'  => ['_' => time()], // cache-buster
    ]);

    $data = json_decode($resp, true);
    if (!is_array($data) || !isset($data['results']) || !is_array($data['results'])) {
        return '<p>Error: No data returned</p>';
    }
    $movies = $data['results'];

    /********************************************************************
     * INLINE CSS: scroll wrapper + sticky header (SOLID + divider line)
     ********************************************************************/
    $html = '
<style>
  /* The scroll viewport: shows ~5-ish rows then scrolls */
  .mc-table-scroll-wrapper {
    width: fit-content;
    max-width: 100%;
    margin: 0 auto;
    display: block;

    max-height: 700px;     /* tweak this for how many rows you want visible */
    overflow-y: auto;
    overflow-x: auto;

    border: 2px solid black;
    border-radius: 4px;

    -webkit-overflow-scrolling: touch;

    position: relative;
    isolation: isolate;    /* keeps site dropdowns above sticky header */
    z-index: 0;
  }

  .mc-table-scroll-wrapper table {
    min-width: 1100px;     /* your existing min width */
    width: max-content;
    border-collapse: collapse;
    font-size: 14px;
    border: none;          /* border is on wrapper */
  }

  /* Make header rows opaque */
  .mc-table-scroll-wrapper thead,
  .mc-table-scroll-wrapper thead tr {
    background-color: #3a0f14; /* match your maroon header */
  }

  /* Sticky header cells */
  .mc-table-scroll-wrapper thead th.table-title-cells-style {
    position: sticky;
    top: 0;
    z-index: 5;

    background-color: #3a0f14; /* solid */
    background-clip: padding-box;

    /* sticky-safe bottom divider line that ALWAYS stays visible */
    border-bottom: none !important;
    box-shadow: inset 0 -1px 0 var(--default_table_text_color);
  }
</style>
';

    // Sort bar (client-side)
    $html .= '
  <div class="movie-sortbar" style="margin:8px 0; display:flex; gap:8px; align-items:center;">
    <label for="club-sort" style="font-weight:600;">Sort by:</label>
    <select id="club-sort" class="club-sort">
      <option value="avg_desc">Club Average — high → low</option>
      <option value="avg_asc">Club Average — low → high</option>
      <option value="count_desc"># Reviews — high → low</option>
      <option value="count_asc"># Reviews — low → high</option>
      <option value="title_az">Title — A → Z</option>
      <option value="director_az">Director — A → Z</option>
    </select>
  </div>';

    // Table (wrapped)
    $html .= '<div class="mc-table-scroll-wrapper">
  <table id="club-avg-table" style="border-collapse:collapse; min-width:1100px; font-size:14px; border:2px solid black;">
    <thead>
      <tr>
        <th class = "table-title-cells-style">Movie</th>
        <th class = "table-title-cells-style summary-column-width">Summary</th>
        <th class = "table-title-cells-style short-info-column-width">Director</th>
        <th class = "table-title-cells-style actors-column-width">Actors</th>
        <th class = "table-title-cells-style short-info-column-width">Genres</th>
        <th class = "table-title-cells-style">Club Average</th>
        <th class = "table-title-cells-style"># Reviews</th>
      </tr>
    </thead>
    <tbody>';

    foreach ($movies as $movie) {
        // FIX: was $r['title'] (undefined) -> should be $movie['title']
        $title    = $movie['title'] ?? '';

        // Your backend might send 'director' or 'directors' (JSONField). Normalize:
        $director = club_safe_implode($movie['director'] ?? ($movie['directors'] ?? ''));
        $actors   = club_safe_implode($movie['actors'] ?? '');
        $genres   = club_safe_implode($movie['genres'] ?? '');
        $summary  = $movie['summary'] ?? '';

        $avg      = isset($movie['avg_rating']) ? (float)$movie['avg_rating'] : null;
        $count    = (int)($movie['num_reviews'] ?? 0);

        $avg_color = function_exists('color_rating_cell') ? color_rating_cell($avg) : '';

        // Data attributes for accurate sorting
        $html .= '<tr
          data-sort-title="'   . esc_attr(strtolower($title))    . '"
          data-sort-director="' . esc_attr(strtolower($director)) . '"
          data-sort-avg="'     . esc_attr($avg !== null ? $avg : -1) . '"
          data-sort-count="'   . esc_attr($count) . '">';

        $html .= '<td class="table-title-cells-style">
                    <div class="movie-poster-tooltip" data-title="' . esc_attr($movie['title']) . '">
                        <img src="' . esc_url($movie['poster_url']) . '"
                            alt="' . esc_attr($movie['title']) . '"
                            style="max-width:120px;height:auto;border-radius:4px;" />
                    </div>
                  </td>';

        $html .= '<td class = "tables-small-data-style">' . esc_html($summary) . '</td>';
        $html .= '<td class = "tables-small-data-style">' . esc_html($director) . '</td>';
        $html .= '<td class = "tables-small-data-style">' . esc_html($actors) . '</td>';
        $html .= '<td class = "tables-small-data-style">' . esc_html($genres) . '</td>';

        $html .= '<td class="avg-cell tables-small-data-style" data-rating="' . esc_attr($avg !== null ? $avg : '') . '" style="border:1px solid black; padding:6px; text-align:center; background-color:' . esc_attr($avg_color) . ';">'
              .     esc_html($avg !== null ? number_format($avg, 2) : '') . '</td>';

        $html .= '<td class = "tables-small-data-style">' . esc_html($count) . '</td>';

        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    // Sorting script (simple + stable)
    $html .= '<script>
(function(){
  function asNumber(v){ if(v===null||v===undefined) return NaN; const n=parseFloat(String(v).replace(",", ".")); return Number.isFinite(n)?n:NaN; }
  function key(row, mode){
    switch(mode){
      case "avg_desc":
      case "avg_asc":   return asNumber(row.getAttribute("data-sort-avg"));
      case "count_desc":
      case "count_asc": return asNumber(row.getAttribute("data-sort-count"));
      case "title_az":  return row.getAttribute("data-sort-title")   || "";
      case "director_az": return row.getAttribute("data-sort-director") || "";
      default: return "";
    }
  }
  function compare(a,b,isNum,asc){
    if(isNum){
      const aN=Number.isNaN(a), bN=Number.isNaN(b);
      if(aN&&bN) return 0;
      if(aN) return 1;
      if(bN) return -1;
      return asc? a-b : b-a;
    }
    if(a===b) return 0;
    return asc ? (a<b?-1:1) : (a>b?-1:1);
  }
  function sortTable(mode){
    const t=document.getElementById("club-avg-table"); if(!t) return;
    const tb=t.querySelector("tbody")||t;
    const rows=Array.from(tb.querySelectorAll("tr"));
    const asc=mode.endsWith("_asc");
    const numeric=new Set(["avg_desc","avg_asc","count_desc","count_asc"]).has(mode);
    const indexed=rows.map((row,i)=>({row,i,key:key(row,mode)}));
    indexed.sort((A,B)=>{ const c=compare(A.key,B.key,numeric,asc); return c!==0?c:(A.i-B.i); });
    indexed.forEach(x=>tb.appendChild(x.row));
  }
  document.addEventListener("DOMContentLoaded", function(){
    const sel=document.getElementById("club-sort"); if(!sel) return;
    sel.addEventListener("change", function(){ sortTable(this.value); });

    // Sort by average high to low by default
    sel.value = "avg_desc";
    sortTable("avg_desc");
  });
})();
</script>';

    /* This section displays the TMDB logo at the bottom of the tables with a short message. This is the attribution */
    $html .= '
<div class="tmdb-attribution" style="
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:6px;
    margin-top:10px;
    font-size:12px;
    color:#aaa;
    text-align:center;
">
    <img
        src="https://www.themoviedb.org/assets/2/v4/logos/v2/blue_long_1-8ba2ac31f354005783fab473602c34c3f4fd207150182061e425d366e4f34596.svg"
        alt="The Movie Database (TMDB)"
        style="height:10px;"
        loading="lazy"
    />
    <span>
        The Movie portion of TnT Movie Club uses the TMDB API but is not endorsed or certified by TMDB.
    </span>
</div>';

    return $html;
});
