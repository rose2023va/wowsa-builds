<?php if (!user::isUserLogged($_COOKIE)) return; ?>

<style>
.ca-choice { max-width: 720px; margin: 0 auto; padding: 32px 16px; font-family: inherit; }
.ca-choice-title {
  font-size: 22px;
  font-weight: 700;
  color: #1a3a2e;
  margin-bottom: 6px;
}
.ca-choice-sub {
  font-size: 14px;
  color: #6b7b74;
  margin-bottom: 32px;
}
.ca-choice-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 600px) {
  .ca-choice-grid { grid-template-columns: 1fr; }
}
.ca-card {
  border-radius: 12px;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  text-decoration: none;
  border: 2px solid #d0e8dc;
  background: #f7fbf9;
  transition: border-color 0.15s, background 0.15s;
}
.ca-card:hover { border-color: #2e7d5e; background: #eef7f2; }
.ca-card-desc br { display: block; content: ''; margin-top: 4px; }
.ca-card-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 20px;
  width: fit-content;
}
.ca-badge-free  { background: #d0f0e2; color: #1a6644; }
.ca-badge-paid  { background: #fef3c7; color: #92400e; }
.ca-card-icon { font-size: 32px; }
.ca-card-label {
  font-size: 17px;
  font-weight: 700;
  color: #1a3a2e;
  line-height: 1.3;
}
.ca-card-desc {
  font-size: 13px;
  color: #4a6a5a;
  line-height: 1.6;
  flex-grow: 1;
}
.ca-card-btn {
  display: inline-block;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  text-align: center;
}
.ca-btn-free { background: #2e7d5e; color: #fff; }
.ca-btn-paid { background: #92400e; color: #fff; }
.ca-card:hover .ca-btn-free { background: #1e5c44; }
.ca-card:hover .ca-btn-paid { background: #78340f; }
</style>

<div class="ca-choice">
  <div class="ca-choice-title">Got something to share? &#127754;</div>
  <div class="ca-choice-sub">Pre-event previews, post-event recaps, press releases, club news, milestone announcements - if it matters to the open water swimming community, this is the place for it. Choose how far you want it to go.</div>

  <div class="ca-choice-grid">

    <a class="ca-card" href="/account/communityannouncements/add">
      <div class="ca-card-badge ca-badge-free">Free - Unlimited</div>
      <div class="ca-card-icon">&#128240;</div>
      <div class="ca-card-label">Post to the Directory</div>
      <div class="ca-card-desc">
        Your announcement goes live on the WOWSA directory instantly, no cap, no waiting. Perfect for:<br><br>
        &#127947; Pre-event previews<br>
        &#127942; Post-event recaps<br>
        &#128227; Press releases<br>
        &#127944; Club &amp; community news
      </div>
      <div class="ca-card-btn ca-btn-free">Publish Free &rarr;</div>
    </a>

    <a class="ca-card" href="/account/communityannouncement-blog-social/add">
      <div class="ca-card-badge ca-badge-paid">+$49 one-time</div>
      <div class="ca-card-icon">&#128640;</div>
      <div class="ca-card-label">Post to the Directory + Get Featured on Blog &amp; Social</div>
      <div class="ca-card-desc">
        Everything in the free option, plus WOWSA puts your announcement on our blog and blasts it across our social channels. Get in front of our full community, not just directory visitors.<br><br>
        &#9989; Instant directory post<br>
        &#9989; Featured on WOWSA blog<br>
        &#9989; Shared to WOWSA social
      </div>
      <div class="ca-card-btn ca-btn-paid">Add Blog &amp; Social &rarr;</div>
    </a>

  </div>
</div>
