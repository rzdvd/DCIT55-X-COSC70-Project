function loadAuthStatus() {
  fetch('check-auth.php')
    .then(res => res.json())
    .then(auth => {
      const navRight = document.getElementById('navRightSection');
      if (navRight) {
        if (auth.isLoggedIn) {
          navRight.innerHTML = `
            <div class="profile-section" id="profileToggle">
              <img src="${escapeHtml(auth.userProfilePic)}" alt="Profile" class="profile-pic" />
              <span class="profile-name">${escapeHtml(auth.userName)}</span>
              <div class="profile-menu" id="profileMenu">
                <a href="profile.html">My Profile</a>
                <a href="bookings.html">My Bookings</a>
                <a href="favorites.html">Favorites</a>
                <button onclick="logout()">Logout</button>
              </div>
            </div>
          `;
          setupProfileMenuListener();
        } else {
          navRight.innerHTML = '<a href="login.html" class="signin-top">Sign In</a>';
        }
      }
    })
    .catch(err => console.error('Failed to load auth status:', err));
}

function setupProfileMenuListener() {
  const profileToggle = document.getElementById('profileToggle');
  const profileMenu = document.getElementById('profileMenu');
  if (profileToggle && profileMenu) {
    profileToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      profileMenu.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
      if (!profileToggle.contains(e.target)) {
        profileMenu.classList.remove('active');
      }
    });
  }
}

function logout() {
  fetch('logout.php', { method: 'POST' })
    .then(() => {
      window.location.href = 'listings.html';
    });
}

function escapeHtml(str) {
  return String(str || '').replace(/[&<>"']/g, function(m) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
  });
}

document.addEventListener('DOMContentLoaded', function() {
  loadAuthStatus();
});
