# AI Post Generation Metrics Display

**Date:** 07.11.2025, 10:25  
**Task:** Add Time, Total Tokens, and Avg Speed to AI post display  
**Token Usage:** ~3,000 tokens

---

## Overview

Added generation metrics display to completed AI posts in the wall feed, showing:
- **Time** - Generation time in seconds
- **Total Tokens** - Total tokens used
- **Avg Speed** - Average tokens per second

---

## Changes Made

### 1. Backend - Post Model (`src/Models/Post.php`)

**SQL Query Update:**
Added two new fields to the wall posts query:
- `job.tokens_per_second` - Average speed from ai_generation_jobs
- `job.elapsed_time` - Elapsed time in milliseconds

**Public Data Update:**
Extended the `ai_content` object to include:
```php
'tokens_per_second' => isset($post['tokens_per_second']) ? (float)$post['tokens_per_second'] : null,
'elapsed_time' => isset($post['elapsed_time']) ? (int)$post['elapsed_time'] : null,
```

### 2. Frontend - WallView Component (`frontend/src/views/WallView.vue`)

**New Helper Function:**
Added `getAverageSpeed(post)` function that:
1. First tries to use stored `tokens_per_second` from database
2. Falls back to calculating from `output_tokens / (generation_time / 1000)`
3. Returns 0 if no data available

**Template Update:**
Added new `ai-generation-stats` section in the completed AI post preview:
```vue
<div class="ai-generation-stats" v-if="post.ai_content?.generation_time || post.ai_content?.total_tokens">
  <div v-if="post.ai_content?.generation_time" class="stat-item">
    <strong>Time:</strong> 
    <span class="stat-value">{{ (post.ai_content.generation_time / 1000).toFixed(2) }}s</span>
  </div>
  <div v-if="post.ai_content?.total_tokens" class="stat-item">
    <strong>Total Tokens:</strong> 
    <span class="stat-value">{{ post.ai_content.total_tokens.toLocaleString() }}</span>
  </div>
  <div v-if="getAverageSpeed(post) > 0" class="stat-item">
    <strong>Avg Speed:</strong> 
    <span class="stat-value">{{ getAverageSpeed(post).toFixed(2) }} tok/s</span>
  </div>
</div>
```

**CSS Styling:**
Added `.ai-generation-stats` styles:
- Flexbox layout with gap spacing
- Light purple background with border
- Monospace font for stat values
- Uppercase labels with letter spacing
- Responsive wrapping

---

## Visual Result

Posts now display generation metrics in a highlighted box below the status info:

```
Status: ✓ Completed  |  Bricks spent: 4 🧱  |  Model: gpt-oss:20b

┌─────────────────────────────────────────────────────┐
│ TIME: 23.45s  │  TOTAL TOKENS: 1,234  │  AVG SPEED: 52.62 tok/s │
└─────────────────────────────────────────────────────┘

Prompt: How to write hello world in Go...
Response: ...
```

---

## Files Modified

1. `src/Models/Post.php` - Added fields to SQL query and public data
2. `frontend/src/views/WallView.vue` - Added helper function, template section, and CSS

---

## Deployment

**Build Status:** ✅ Completed successfully
```
vite v5.4.21 building for production...
✓ 215 modules transformed.
../public/assets/WallView-D7EmfC1T.js  72.07 kB │ gzip: 21.71 kB
```

**Production Deployment:**
To deploy to production server:
```bash
# On production server (wall.cyka.lol)
cd /var/www/wall.cyka.lol
rm -f public/assets/WallView-*.js public/assets/WallView-*.css

# Then copy new files from local to production
# Or rebuild on production server
```

---

## Testing

1. Navigate to any wall with completed AI posts
2. Verify the metrics section appears below status info
3. Check that Time, Total Tokens, and Avg Speed display correctly
4. Ensure formatting looks good (2 decimal places for time/speed, comma separators for tokens)

---

## Notes

- The average speed is calculated from `output_tokens / (generation_time / 1000)` if `tokens_per_second` is not available in the database
- The `generation_time` is stored in milliseconds, so we divide by 1000 for display
- All metrics are optional and only show if data is available
- The design matches the existing AI generation info styling

