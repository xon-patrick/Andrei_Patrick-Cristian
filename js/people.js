let allUsers = [];
let filteredUsers = [];

document.addEventListener('DOMContentLoaded', () => {
  loadUsers();
  setupSearch();
});

async function loadUsers() {
  const loading = document.getElementById('loading');
  const container = document.getElementById('usersContainer');
  const emptyState = document.getElementById('emptyState');
  
  loading.style.display = 'block';
  container.innerHTML = '';
  emptyState.style.display = 'none';

  try {
    const response = await fetch('get_users.php');
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.error || 'Failed to load users');
    }

    allUsers = data.users || [];
    filteredUsers = [...allUsers];
    displayUsers(filteredUsers);
    
  } catch (error) {
    console.error('Error loading users:', error);
    container.innerHTML = `
      <div class="error-message">
        <p>Failed to load users. Please try again.</p>
      </div>
    `;
  } finally {
    loading.style.display = 'none';
  }
}

function displayUsers(users) {
  const container = document.getElementById('usersContainer');
  const emptyState = document.getElementById('emptyState');
  
  if (users.length === 0) {
    container.innerHTML = '';
    emptyState.style.display = 'block';
    return;
  }
  
  emptyState.style.display = 'none';
  
  container.innerHTML = users.map(user => {
    const avatar = user.profile_picture || 'profile.jpeg';
    const bio = user.bio || 'Film enthusiast';
    const followersCount = parseInt(user.followers_count) || 0;
    const followingCount = parseInt(user.following_count) || 0;
    const isFollowing = user.is_following == 1;
    
    return `
      <div class="user-card" data-user-id="${user.user_id}">
        <div class="user-avatar-container">
          <img src="${avatar}" alt="${user.username}" class="user-avatar" 
               onerror="this.src='profile.jpeg'" />
        </div>
        
        <div class="user-info">
          <h3 class="user-name">${escapeHtml(user.username)}</h3>
          <p class="user-bio">${escapeHtml(bio)}</p>
          
          <div class="user-stats">
            <span class="stat">
              <strong>${followersCount}</strong> followers
            </span>
            <span class="stat">
              <strong>${followingCount}</strong> following
            </span>
          </div>
        </div>
        
        <div class="user-actions">
          ${typeof IS_LOGGED_IN !== 'undefined' && IS_LOGGED_IN ? `
            <button class="btn-follow ${isFollowing ? 'following' : ''}" 
                    data-user-id="${user.user_id}"
                    onclick="toggleFollow(${user.user_id}, this)">
              ${isFollowing ? 'Following' : 'Follow'}
            </button>
          ` : `
            <a href="login.html" class="btn-follow-login">
              Login to Follow
            </a>
          `}
          <a href="view_profile.php?user_id=${user.user_id}" class="btn-profile">
            View Profile
          </a>
        </div>
      </div>
    `;
  }).join('');
}

async function toggleFollow(userId, button) {
  // Check if user is logged in
  if (typeof IS_LOGGED_IN === 'undefined' || !IS_LOGGED_IN) {
    window.location.href = 'login.html';
    return;
  }
  
  const originalText = button.textContent;
  const wasFollowing = button.classList.contains('following');
  
  button.disabled = true;
  button.textContent = '...';
  
  try {
    const formData = new FormData();
    formData.append('user_id', userId);
    
    const response = await fetch('toggle_follow.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.error || 'Failed to toggle follow');
    }
    
    if (data.is_following) {
      button.classList.add('following');
      button.textContent = 'Following';
    } else {
      button.classList.remove('following');
      button.textContent = 'Follow';
    }
    
    const userIndex = allUsers.findIndex(u => u.user_id == userId);
    if (userIndex !== -1) {
      const currentCount = parseInt(allUsers[userIndex].followers_count) || 0;
      allUsers[userIndex].followers_count = data.is_following ? currentCount + 1 : currentCount - 1;
      allUsers[userIndex].is_following = data.is_following ? 1 : 0;
      
      const filteredIndex = filteredUsers.findIndex(u => u.user_id == userId);
      if (filteredIndex !== -1) {
        filteredUsers[filteredIndex] = allUsers[userIndex];
      }
      
      updateUserStats(userId);
    }
    
  } catch (error) {
    console.error('Error toggling follow:', error);
    alert('Failed to update follow status. Please try again.');
    button.textContent = originalText;
    if (wasFollowing) {
      button.classList.add('following');
    } else {
      button.classList.remove('following');
    }
  } finally {
    button.disabled = false;
  }
}

function updateUserStats(userId) {
  const user = allUsers.find(u => u.user_id == userId);
  if (!user) return;
  
  const userCard = document.querySelector(`.user-card[data-user-id="${userId}"]`);
  if (!userCard) return;
  
  const statsDiv = userCard.querySelector('.user-stats');
  if (statsDiv) {
    const followersCount = parseInt(user.followers_count) || 0;
    const followingCount = parseInt(user.following_count) || 0;
    
    statsDiv.innerHTML = `
      <span class="stat">
        <strong>${followersCount}</strong> followers
      </span>
      <span class="stat">
        <strong>${followingCount}</strong> following
      </span>
    `;
  }
}

function setupSearch() {
  const searchInput = document.getElementById('userSearch');
  let debounceTimer;
  
  searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const searchTerm = e.target.value.toLowerCase().trim();
      
      if (searchTerm === '') {
        filteredUsers = [...allUsers];
      } else {
        filteredUsers = allUsers.filter(user => 
          user.username.toLowerCase().includes(searchTerm) ||
          (user.email && user.email.toLowerCase().includes(searchTerm)) ||
          (user.bio && user.bio.toLowerCase().includes(searchTerm))
        );
      }
      
      displayUsers(filteredUsers);
    }, 300);
  });
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
