const map = L.map("map").setView([14.1966, 120.88229], 16);

L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  attribution:
    '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
}).addTo(map);

const markerLayer = L.layerGroup().addTo(map);

let dormData = [];
let filteredDorms = [];
let amenitiesByDorm = {};
let favoritesByDorm = {};
let selectedFilters = {
  amenities: [],
  roomType: []
};

function loadDormData() {
  fetch('listings.php?json=1')
    .then(res => { if (!res.ok) throw new Error(res.statusText); return res.json(); })
    .then(({dorms, amenities}) => {
      dormData = dorms || [];
      filteredDorms = [...dormData];
      
      amenitiesByDorm = {};
      (amenities||[]).forEach(a => {
        amenitiesByDorm[a.dorm_id] = amenitiesByDorm[a.dorm_id] || [];
        amenitiesByDorm[a.dorm_id].push(a.amenity_name.toLowerCase());
      });
      
      renderDormCards(filteredDorms);
      renderMarkers(filteredDorms);
      updateResultCount();
    })
    .catch(err => console.error('Failed to load dorms', err));
}

function renderDormCards(dorms) {
  const feed = document.getElementById('dormCardsFeed');
  feed.innerHTML = '';

  if (dorms.length === 0) {
    feed.innerHTML = '<div style="color: #a0aec0; padding: 2rem; text-align: center;">No dorms match your search criteria.</div>';
    return;
  }

  dorms.forEach((dorm, index) => {
    const card = document.createElement('div');
    card.className = `dorm-card ${index === 0 ? 'active' : ''}`;
    card.dataset.index = index;
    card.dataset.dormId = dorm.dorm_id;

    const amenities = amenitiesByDorm[dorm.dorm_id] || [];
    const roomType = dorm.room_capacity > 1 ? 'shared' : 'solo';
    const isFavorite = favoritesByDorm[dorm.dorm_id] || false;
    
    card.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px;">
        <div style="flex: 1;">
          ${amenities.includes('parking') ? '<span class="view-tag">Has Parking</span>' : ''}
          <h3>${dorm.dorm_name}</h3>
          <p class="location">${dorm.address}</p>
          <p class="owner-name">Owner: ${escapeHtml(dorm.owner_first_name || 'Unknown')} ${escapeHtml(dorm.owner_last_name || '')}</p>
          <span class="unit-type">${roomType.charAt(0).toUpperCase() + roomType.slice(1)} Units</span>
          <span class="status-badge immediate">Immediate Avail</span>
        </div>
        <button class="favorite-heart-btn ${isFavorite ? 'active' : ''}" data-dorm-id="${dorm.dorm_id}" title="Add to favorites">
          <i class="fas fa-heart"></i>
        </button>
      </div>
    `;

    card.addEventListener('click', (e) => {
      if (e.target.closest('.favorite-heart-btn')) {
        e.stopPropagation();
        return;
      }
      document.querySelectorAll('.dorm-card').forEach(c => c.classList.remove('active'));
      card.classList.add('active');
      updatePanel(dorm);
    });

    const favoriteBtn = card.querySelector('.favorite-heart-btn');
    favoriteBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleFavorite(dorm.dorm_id, favoriteBtn);
    });

    feed.appendChild(card);
  });

  if (dorms.length > 0) {
    updatePanel(dorms[0]);
  }
}

function updatePanel(dorm) {
  document.querySelector('.panel-block.header-block h2').textContent = dorm.dorm_name;
  const ownerName = dorm.owner_first_name ? `${escapeHtml(dorm.owner_first_name)} ${escapeHtml(dorm.owner_last_name)}` : 'Unknown';
  document.querySelector('.sub-facility').innerHTML =
    `<i class="fa-solid fa-house detail-icon-img"></i> <span>${dorm.address}</span><br><small>Owner: ${ownerName}</small>`;
  document.querySelector('.price-range').textContent = `PHP ${dorm.monthly_rent} a month`;
  document.querySelector('.rent-text').textContent = `PHP ${dorm.monthly_rent} a month (Deposit and Advance required)`;
  
  const amenities = amenitiesByDorm[dorm.dorm_id] || [];
  const roomTypeText = dorm.room_capacity > 1 ? 'Shared' : 'Solo';
  document.querySelector('.type-text').textContent = roomTypeText;
  
  const amenitiesList = amenities.length > 0 ? `<strong>Amenities:</strong> ${amenities.join(', ')}<br>` : '';
  const inferredRoomType = dorm.room_capacity > 1 ? 'Shared' : 'Solo';
  document.querySelector('.scroll-text').innerHTML = `
    <p>${dorm.description || 'Contact landlord for more information.'}</p>
    <span class="section-label">Rent:</span>
    <p>PHP ${dorm.monthly_rent} a month</p>
    <span class="section-label">Room Type:</span>
    <p>${inferredRoomType}</p>
  `;

  const bookButton = document.querySelector('.btn-primary.glow-effect');
  if (bookButton) {
    bookButton.onclick = function() {
      window.location.href = `listings/booking.html?dorm_id=${dorm.dorm_id}`;
    };
  }
}

function applyFilters() {
  const searchDorm = document.getElementById('searchDormName').value.toLowerCase();
  const searchLocation = document.getElementById('searchLocation').value.toLowerCase();
  const activeAmenities = selectedFilters.amenities;
  const activeRoomTypes = selectedFilters.roomType;

  filteredDorms = dormData.filter(dorm => {
    const matchesDormName = dorm.dorm_name.toLowerCase().includes(searchDorm);
    const matchesLocation = dorm.address.toLowerCase().includes(searchLocation);
    const matchesSearch = (searchDorm === '' || matchesDormName) && (searchLocation === '' || matchesLocation);
    
    if (!matchesSearch) return false;

    if (activeAmenities.length > 0) {
      const dormAmenities = amenitiesByDorm[dorm.dorm_id] || [];
      const hasAllAmenities = activeAmenities.every(amenity => dormAmenities.includes(amenity));
      if (!hasAllAmenities) return false;
    }

    if (activeRoomTypes.length > 0) {
      const roomType = dorm.room_capacity > 1 ? 'shared' : 'solo';
      if (!activeRoomTypes.includes(roomType)) return false;
    }

    return true;
  });

  renderDormCards(filteredDorms);
  renderMarkers(filteredDorms);
  updateResultCount();
}

function updateResultCount() {
  document.getElementById('resultCount').textContent = filteredDorms.length;
}

function clearMarkers() {
  markerLayer.clearLayers();
}

function renderMarkers(dorms) {
  clearMarkers();
  dorms.forEach(dorm => {
    if (dorm.latitude && dorm.longitude) {
      const marker = L.marker([dorm.latitude, dorm.longitude]);
      marker.bindPopup(`<b>${escapeHtml(dorm.dorm_name)}</b><br>${escapeHtml(dorm.address)}`);
      marker.on('click', function() {
        const card = document.querySelector(`.dorm-card[data-dorm-id="${dorm.dorm_id}"]`);
        if (card) {
          card.click();
        }
      });
      markerLayer.addLayer(marker);
    }
  });

  if (markerLayer.getLayers().length > 0) {
    map.fitBounds(markerLayer.getBounds().pad(0.1));
  }
}

function escapeHtml(str) {
  return String(str || '').replace(/[&<>"']/g, function(m) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
  });
}

document.addEventListener('DOMContentLoaded', function() {
  loadDormData();

  document.getElementById('searchDormName').addEventListener('input', applyFilters);
  document.getElementById('searchLocation').addEventListener('input', applyFilters);
  document.getElementById('findBtn').addEventListener('click', applyFilters);

  document.querySelectorAll('.tag').forEach(tag => {
    tag.addEventListener('click', function() {
      this.classList.toggle('active');
      const filter = this.dataset.filter;
      
      if (filter === 'aircon') {
        const index = selectedFilters.amenities.indexOf('aircon');
        if (index > -1) {
          selectedFilters.amenities.splice(index, 1);
        } else {
          selectedFilters.amenities.push('aircon');
        }
      } else if (filter === 'parking') {
        const index = selectedFilters.amenities.indexOf('parking');
        if (index > -1) {
          selectedFilters.amenities.splice(index, 1);
        } else {
          selectedFilters.amenities.push('parking');
        }
      } else if (filter === 'solo') {
        const index = selectedFilters.roomType.indexOf('solo');
        if (index > -1) {
          selectedFilters.roomType.splice(index, 1);
        } else {
          selectedFilters.roomType.push('solo');
        }
      } else if (filter === 'shared') {
        const index = selectedFilters.roomType.indexOf('shared');
        if (index > -1) {
          selectedFilters.roomType.splice(index, 1);
        } else {
          selectedFilters.roomType.push('shared');
        }
      } else if (filter === 'wifi') {
        const index = selectedFilters.amenities.indexOf('wifi');
        if (index > -1) {
          selectedFilters.amenities.splice(index, 1);
        } else {
          selectedFilters.amenities.push('wifi');
        }
      }
      
      applyFilters();
    });
  });

  // Load favorites on page load
  loadUserFavorites();
});

function loadUserFavorites() {
  // Check all dorms to see which are favorited
  dormData.forEach(dorm => {
    fetch(`listings/favorites.php?action=check&dorm_id=${dorm.dorm_id}`)
      .then(res => res.json())
      .then(data => {
        if (data.is_favorite) {
          favoritesByDorm[dorm.dorm_id] = true;
          updateFavoriteButton(dorm.dorm_id, true);
        }
      })
      .catch(err => console.error('Error checking favorite:', err));
  });
}

function updateFavoriteButton(dormId, isFavorite) {
  const btn = document.querySelector(`.favorite-heart-btn[data-dorm-id="${dormId}"]`);
  if (btn) {
    if (isFavorite) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  }
}

function toggleFavorite(dormId, button) {
  const formData = new FormData();
  formData.append('dorm_id', dormId);
  formData.append('action', 'toggle');

  fetch('favorites.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (data.action === 'added') {
        favoritesByDorm[dormId] = true;
        updateFavoriteButton(dormId, true);
      } else if (data.action === 'removed') {
        favoritesByDorm[dormId] = false;
        updateFavoriteButton(dormId, false);
      }
    }
  })
  .catch(err => console.error('Error toggling favorite:', err));
}
