export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'https://admin.qismatconnections.com/api/v1';

const TOKEN_KEY = 'qismat.member.token';
export const session = {
  get: () => sessionStorage.getItem(TOKEN_KEY),
  set: (token) => sessionStorage.setItem(TOKEN_KEY, token),
  clear: () => sessionStorage.removeItem(TOKEN_KEY),
};

async function request(path, options = {}) {
  const token = options.token ?? session.get();
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload?.success) {
    const validationMessage = Object.values(payload?.errors || {}).flat()[0];
    throw new Error(validationMessage || payload?.message || 'The request could not be completed.');
  }
  return payload;
}

export async function exchangeFirebaseToken(idToken) {
  const payload = await request('/auth/firebase', { method: 'POST', token: idToken });
  session.set(payload.data.token);
  return payload.data;
}

export const getCurrentUser = () => request('/auth/me').then(({ data }) => data);
export const getProfile = () => request('/profile').then(({ data }) => data);
export const getPartnerPreferences = () => request('/profile/partner-preferences').then(({ data }) => data);
export const getOnboardingStatus = () => request('/profile/onboarding-status').then(({ data }) => data);
export const updateProfile = (profile) => request('/profile', { method: 'PUT', body: JSON.stringify(profile) }).then(({ data }) => data);
export const updatePartnerPreferences = (preferences) => request('/profile/partner-preferences', { method: 'PUT', body: JSON.stringify(preferences) }).then(({ data }) => data);
export const getProfilePhotos = () => request('/profile/photos').then(({ data }) => data);
export const uploadProfilePhoto = (photo, visibility = 'members') => {
  const body = new FormData();
  body.append('photo', photo);
  body.append('visibility', visibility);
  return request('/profile/photos', { method: 'POST', body }).then(({ data }) => data);
};
export const updateProfilePhoto = (photoId, changes) => request(`/profile/photos/${photoId}`, { method: 'PATCH', body: JSON.stringify(changes) }).then(({ data }) => data);
export const reorderProfilePhotos = (photoIds) => request('/profile/photos/order', { method: 'PUT', body: JSON.stringify({ photo_ids: photoIds }) }).then(({ data }) => data);
export const deleteProfilePhoto = (photoId) => request(`/profile/photos/${photoId}`, { method: 'DELETE' }).then(({ data }) => data);
export async function getProfilePhotoBlob(contentUrl) {
  const response = await fetch(contentUrl, {
    headers: { Accept: 'image/*', Authorization: `Bearer ${session.get()}` },
  });
  if (!response.ok) throw new Error('The photo could not be loaded.');
  return response.blob();
}
export const submitProfile = () => request('/profile/submit', { method: 'POST' }).then(({ data }) => data);
export const updateDiscovery = (enabled) => request('/profile/discovery', { method: 'PUT', body: JSON.stringify({ enabled }) }).then(({ data }) => data);
export const getMatches = (filters = {}) => {
  const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value !== '' && value != null));
  return request(`/matches?${query}`).then(({ data }) => data);
};
export const getMatch = (profileId) => request(`/matches/${profileId}`).then(({ data }) => data);
export const getFavourites = () => request('/favourites').then(({ data }) => data);
export const saveFavourite = (profileId) => request('/favourites', { method: 'POST', body: JSON.stringify({ profile_id: profileId }) }).then(({ data }) => data);
export const removeFavourite = (profileId) => request(`/favourites/${profileId}`, { method: 'DELETE' }).then(({ data }) => data);
export const sendInterest = (receiverId, message = null) => request('/interests', { method: 'POST', body: JSON.stringify({ receiver_id: receiverId, message }) }).then(({ data }) => data);
export const getInterests = (direction = '') => request(`/interests${direction ? `?direction=${direction}` : ''}`).then(({ data }) => data);
export const respondToInterest = (interestId, status) => request(`/interests/${interestId}/respond`, { method: 'POST', body: JSON.stringify({ status }) }).then(({ data }) => data);
export const cancelInterest = (interestId) => request(`/interests/${interestId}`, { method: 'DELETE' }).then(({ data }) => data);
export const blockMember = (userId, reason = null) => request('/blocks', { method: 'POST', body: JSON.stringify({ user_id: userId, reason }) }).then(({ data }) => data);
export const reportMember = (userId, reason, details = null) => request('/reports', { method: 'POST', body: JSON.stringify({ user_id: userId, reason, details }) }).then(({ data }) => data);
export const getBlockedMembers = () => request('/blocks').then(({ data }) => data);
export const unblockMember = (userId) => request(`/blocks/${userId}`, { method: 'DELETE' }).then(({ data }) => data);
export const getNotificationPreferences = () => request('/account/notification-preferences').then(({ data }) => data);
export const updateNotificationPreferences = (preferences) => request('/account/notification-preferences', { method: 'PUT', body: JSON.stringify(preferences) }).then(({ data }) => data);
export const logoutAllApi = () => request('/auth/logout-all', { method: 'POST' }).then(({ data }) => data);
export const deleteAccount = () => request('/account', { method: 'DELETE', body: JSON.stringify({ confirmation: 'DELETE' }) }).then(({ data }) => data);
export const getPhotoAccessRequests = (direction = 'received') => request(`/photo-access-requests?direction=${direction}`).then(({ data }) => data);
export const requestPhotoAccess = (userId) => request('/photo-access-requests', { method: 'POST', body: JSON.stringify({ user_id: userId }) }).then(({ data }) => data);
export const respondPhotoAccess = (requestId, decision) => request(`/photo-access-requests/${requestId}/respond`, { method: 'POST', body: JSON.stringify({ decision }) }).then(({ data }) => data);
export const revokePhotoAccess = (requestId) => request(`/photo-access-requests/${requestId}`, { method: 'DELETE' }).then(({ data }) => data);
export const getConversations = () => request('/conversations').then(({ data }) => data);
export const getMessages = (conversationId) => request(`/conversations/${conversationId}/messages`).then(({ data }) => data);
export const sendMessage = (conversationId, body) => request(`/conversations/${conversationId}/messages`, { method: 'POST', body: JSON.stringify({ body }) }).then(({ data }) => data);
export const markConversationRead = (conversationId) => request(`/conversations/${conversationId}/read`, { method: 'POST' }).then(({ data }) => data);
export const deleteMessage = (messageId) => request(`/messages/${messageId}`, { method: 'DELETE' }).then(({ data }) => data);
export async function logoutApi() {
  try { if (session.get()) await request('/auth/logout', { method: 'POST' }); }
  finally { session.clear(); }
}
