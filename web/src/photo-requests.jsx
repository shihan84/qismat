import React, { useEffect, useState } from 'react';
import { getPhotoAccessRequests, respondPhotoAccess, revokePhotoAccess } from './api';
import './photo-requests.css';

export function PhotoRequests() {
  const [direction, setDirection] = useState('received');
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(null);
  const [error, setError] = useState('');

  async function load(nextDirection = direction) {
    setLoading(true); setError('');
    try { const page = await getPhotoAccessRequests(nextDirection); setItems(page.data || []); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(direction); }, [direction]);

  async function act(item, action) {
    setBusy(item.id); setError('');
    try {
      if (action === 'revoke') await revokePhotoAccess(item.id); else await respondPhotoAccess(item.id, action);
      await load();
    } catch (requestError) { setError(requestError.message); }
    finally { setBusy(null); }
  }

  return <main className="photo-request-page"><div className="photo-request-heading"><div><span className="kicker">Private by design</span><h1>Photo access</h1><p>Private photos stay protected unless their owner approves a specific member.</p></div><div className="request-tabs"><button className={direction === 'received' ? 'active' : ''} onClick={() => setDirection('received')}>Received</button><button className={direction === 'sent' ? 'active' : ''} onClick={() => setDirection('sent')}>Sent</button></div></div>{error && <div className="auth-notice error">{error}</div>}{loading ? <p className="request-empty">Loading requests…</p> : items.length === 0 ? <p className="request-empty">No {direction} photo requests.</p> : <div className="request-list">{items.map((item) => { const member = direction === 'received' ? item.requester : item.owner; return <article key={item.id}><div><strong>{member?.profile?.display_name || member?.name || 'Member'}</strong><small>{member?.profile?.profile_code || 'Profile code unavailable'} · Requested {new Date(item.created_at).toLocaleDateString()}</small></div><span className={`request-status ${item.status}`}>{item.status}</span><div>{direction === 'received' && item.status === 'pending' && <><button disabled={busy === item.id} onClick={() => act(item, 'declined')}>Decline</button><button className="approve" disabled={busy === item.id} onClick={() => act(item, 'approved')}>Approve</button></>}{item.status === 'approved' && <button disabled={busy === item.id} onClick={() => act(item, 'revoke')}>Remove access</button>}{direction === 'sent' && item.status === 'pending' && <button disabled={busy === item.id} onClick={() => act(item, 'revoke')}>Cancel request</button>}</div></article>; })}</div>}</main>;
}
