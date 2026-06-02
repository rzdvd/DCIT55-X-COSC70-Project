const AUTH_BASE_URL = (() => {
  const script = document.currentScript || document.querySelector('script[src*="auth.js"]');
  return script ? new URL('.', script.src) : new URL('.', window.location.href);
})();

function authFetch(path, options) {
  return fetch(new URL(path, AUTH_BASE_URL).href, options);
}

function authLink(path) {
  return new URL(path, AUTH_BASE_URL).href;
}

function loadAuthStatus() {
  authFetch('check-auth.php')
    .then(res => res.json())
    .then(auth => {
      const navRight = document.getElementById('navRightSection');
      if (navRight) {
        if (auth.isLoggedIn) {
          let profileMenuItems = `
            <a href="${authLink('../profile/profile.html')}">My Profile</a>
          `;
          if (auth.userRole === 'student') {
            profileMenuItems += `
              <a href="${authLink('../listings/my-bookings.html')}">My Bookings</a>
              <a href="${authLink('../listings/favorites.html')}">Favorites</a>
            `;
          }
          if (auth.userRole === 'dorm_owner') {
            profileMenuItems += `<a href="${authLink('../admin/admin-dashboard.html')}">Dashboard</a>`;
          }
          profileMenuItems += `<button onclick="logout()">Logout</button>`;
          navRight.innerHTML = `
            <div class="profile-section" id="profileToggle">
              <img src="${escapeHtml(auth.userProfilePic)}" alt="Profile" class="profile-pic" />
              <span class="profile-name">${escapeHtml(auth.userName)}</span>
              <div class="profile-menu" id="profileMenu">
                ${profileMenuItems}
              </div>
            </div>
          `;
          setupProfileMenuListener();
        } else {
          navRight.innerHTML = `<a href="${authLink('login.html')}" class="signin-top">Sign In</a>`;
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
  authFetch('logout.php', { method: 'POST' })
    .then(() => {
      window.location.href = authLink('../listings/listings.html');
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
