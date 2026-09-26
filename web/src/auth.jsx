import React, { useEffect, useState } from 'react';
import { exchangeFirebaseToken, getOnboardingStatus, getPartnerPreferences, getProfile, updateDiscovery } from './api';
import { firebaseConfigured, firebaseGoogleLogin, firebaseLogin, firebaseRegister, firebaseResetPassword, socialLoginConfigured } from './firebase';
import { ProfileEditor } from './profile';
import { Discovery } from './discovery';
import { Interests } from './interests';
import { Messages } from './messages';
import { AccountSettings } from './account';
import { PhotoRequests } from './photo-requests';
import './auth.css';
import './social-auth.css';

export function AuthPanel({ initialMode = 'login', onClose, onAuthenticated }) {
  const [mode, setMode] = useState(initialMode);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [view, setView] = useState('home');
  const [message, setMessage] = useState('');
  const [success, setSuccess] = useState(false);
  const [purposeAccepted, setPurposeAccepted] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true); setMessage(''); setSuccess(false);
    try {
      if (mode === 'register') {
        await firebaseRegister(name, email, password);
        setSuccess(true);
        setMessage('Account created. Check your email to verify it, then sign in.');
        setMode('login'); setPassword('');
      } else {
        const idToken = await firebaseLogin(email, password);
        const data = await exchangeFirebaseToken(idToken);
        onAuthenticated(data.user);
      }
    } catch (error) { setMessage(error.message || 'Unable to continue.'); }
    finally { setBusy(false); }
  }

  async function reset() {
    if (!email) return setMessage('Enter your email first.');
    setBusy(true); setMessage(''); setSuccess(false);
    try { await firebaseResetPassword(email); setSuccess(true); setMessage('Password reset email sent.'); }
    catch (error) { setMessage(error.message || 'Unable to send reset email.'); }
    finally { setBusy(false); }
  }

  async function googleLogin() {
    if (mode === 'register' && !purposeAccepted) {
      setMessage('Confirm that you are joining Qismat for genuine marriage purposes.');
      return;
    }
    setBusy(true); setMessage(''); setSuccess(false);
    try {
      const idToken = await firebaseGoogleLogin();
      const data = await exchangeFirebaseToken(idToken);
      onAuthenticated(data.user);
    } catch (error) { setMessage(error.message || 'Unable to continue with social sign-in.'); }
    finally { setBusy(false); }
  }

  return <div className="auth-backdrop" role="dialog" aria-modal="true" aria-label="Member access"><section className="auth-panel">
    <button className="auth-close" onClick={onClose} aria-label="Close">×</button>
    <span className="kicker">Member access</span><h2>{mode === 'register' ? 'Create your Qismat account' : 'Welcome back'}</h2>
    <p>{mode === 'register' ? 'Start with a verified email. Your profile remains private until you complete it and opt into discovery.' : 'Sign in to continue your profile and connections.'}</p>
    {!firebaseConfigured && <div className="auth-notice error">Sign-in is not configured for this environment.</div>}
    <div className="social-login">
      <button type="button" className="social-button google" onClick={googleLogin} disabled={busy || !socialLoginConfigured}><span aria-hidden="true">G</span>Continue with Google</button>
    </div>
    <div className="auth-divider"><span>or continue with email</span></div>
    <form onSubmit={submit}>
      {mode === 'register' && <label>Full name<input value={name} onChange={(event) => setName(event.target.value)} autoComplete="name" required /></label>}
      <label>Email<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" required /></label>
      <label>Password<input type="password" minLength="8" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete={mode === 'register' ? 'new-password' : 'current-password'} required /></label>
      {mode === 'register' && <label className="purpose-check"><input type="checkbox" checked={purposeAccepted} onChange={(event) => setPurposeAccepted(event.target.checked)} required /><span>I am an adult genuinely seeking marriage. I will provide truthful information and will not use Qismat for scams, solicitation, or commercial marriage-bureau or agency activity.</span></label>}
      {message && <div className={`auth-notice ${success ? 'success' : 'error'}`}>{message}</div>}
      <button className="btn btn-primary auth-submit" disabled={busy || !firebaseConfigured}>{busy ? 'Please wait…' : mode === 'register' ? 'Create account' : 'Sign in'}</button>
    </form>
    <div className="auth-links"><button onClick={() => { setMode(mode === 'register' ? 'login' : 'register'); setMessage(''); }}>{mode === 'register' ? 'Already registered? Sign in' : 'New to Qismat? Create account'}</button>{mode === 'login' && <button onClick={reset}>Forgot password?</button>}</div>
  </section></div>;
}

export function MemberHome({ user, onLogout }) {
  const [status, setStatus] = useState(null);
  const [profile, setProfile] = useState(null);
  const [preferences, setPreferences] = useState(null);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [editing, setEditing] = useState(false);
  const [busy, setBusy] = useState(false);

  async function refresh() {
    setError('');
    try {
      const [nextProfile, nextStatus, nextPreferences] = await Promise.all([getProfile(), getOnboardingStatus(), getPartnerPreferences()]);
      setProfile(nextProfile); setStatus(nextStatus); setPreferences(nextPreferences);
    } catch (requestError) { setError(requestError.message); }
  }
  useEffect(() => { refresh(); }, []);

  async function toggleDiscovery() {
    setBusy(true); setError(''); setNotice('');
    try {
      await updateDiscovery(!status.discovery_opt_in);
      setNotice(status.discovery_opt_in ? 'Your profile is hidden from discovery.' : 'Your profile is now visible in discovery.');
      await refresh();
    } catch (requestError) { setError(requestError.message); }
    finally { setBusy(false); }
  }

  async function completed(_saved, message) {
    setEditing(false); setNotice(message); await refresh();
  }

  const state = status?.moderation_status || 'draft';
  return <div className="member-shell"><header className="member-header"><strong><img src="/assets/qismat-connections-logo.png" alt="Qismat Connections" /></strong><nav className="member-nav" aria-label="Member navigation"><button className={view === 'home' ? 'active' : ''} onClick={() => { setView('home'); setEditing(false); }}>Home</button><button className={view === 'discover' ? 'active' : ''} onClick={() => { setView('discover'); setEditing(false); }}>Discover</button><button className={view === 'saved' ? 'active' : ''} onClick={() => { setView('saved'); setEditing(false); }}>Saved</button><button className={view === 'interests' ? 'active' : ''} onClick={() => { setView('interests'); setEditing(false); }}>Interests</button><button className={view === 'messages' ? 'active' : ''} onClick={() => { setView('messages'); setEditing(false); }}>Messages</button><button className={view === 'photos' ? 'active' : ''} onClick={() => { setView('photos'); setEditing(false); }}>Photo access</button><button className={view === 'settings' ? 'active' : ''} onClick={() => { setView('settings'); setEditing(false); }}>Settings</button></nav><div><span>{user.name}</span><button onClick={onLogout}>Sign out</button></div></header>
    {view === 'discover' ? <Discovery /> : view === 'saved' ? <Discovery mode="saved" /> : view === 'interests' ? <Interests /> : view === 'messages' ? <Messages /> : view === 'photos' ? <PhotoRequests /> : view === 'settings' ? <AccountSettings onSignedOut={onLogout} /> : editing ? <ProfileEditor profile={profile} preferences={preferences} status={status} onCancel={() => setEditing(false)} onComplete={completed} /> : <main className="member-main">
      <section><span className="kicker">Your membership</span><h1>Welcome, {user.name.split(' ')[0]}.</h1><p>Your secure account is connected. Complete your profile, submit it for review, and choose when approved whether to appear in discovery.</p>
        {notice && <div className="auth-notice success">{notice}</div>}{error && <div className="auth-notice error">{error}</div>}
        {state === 'rejected' && status?.moderation_feedback && <div className="member-feedback"><strong>Reviewer feedback</strong><span>{status.moderation_feedback}</span></div>}
        <div className="member-buttons"><button className="btn btn-primary" onClick={() => setEditing(true)}>{profile ? 'Review and edit profile' : 'Complete your profile'}</button>{state === 'approved' && <button className="btn member-discovery" onClick={toggleDiscovery} disabled={busy}>{status.discovery_opt_in ? 'Pause discovery' : 'Enter discovery'}</button>}</div>
      </section>
      <aside className="member-status"><span>Profile status</span><strong>{state}</strong><div className="completion"><i style={{ width: `${status?.profile_completion || 0}%` }}></i></div><small>{status?.profile_completion || 0}% profile completion</small><ul><li className="done">Email verified</li><li className={status?.required_fields_complete ? 'done' : ''}>Required details complete</li><li className={state === 'approved' ? 'done' : ''}>Profile moderation approved</li><li className={status?.has_approved_primary_photo ? 'done' : ''}>Primary photo approved</li><li className={status?.discoverable ? 'done' : ''}>Visible in discovery</li></ul></aside>
    </main>}
  </div>;
}
