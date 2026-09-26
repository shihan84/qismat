import React, { useEffect, useState } from 'react';
import { blockMember, getFavourites, getMatch, getMatches, getProfilePhotoBlob, removeFavourite, reportMember, requestPhotoAccess, saveFavourite, sendInterest } from './api';
import './discovery.css';
import './discovery-gallery.css';
import { COMMUNITIES, DENOMINATIONS, ETHNICITIES, RELIGIONS, SuggestionList } from './profile-options';

function PrivateImage({ photo, alt }) {
  const [source, setSource] = useState('');
  useEffect(() => {
    let url;
    if (!photo?.content_url) return undefined;
    getProfilePhotoBlob(photo.content_url).then((blob) => { url = URL.createObjectURL(blob); setSource(url); }).catch(() => setSource(''));
    return () => { if (url) URL.revokeObjectURL(url); };
  }, [photo?.content_url]);
  return source ? <img src={source} alt={alt} /> : <div className="match-placeholder" aria-label="Photo unavailable">Q</div>;
}

const emptyFilters = { gender: '', min_age: '', max_age: '', country: '', city: '', religion: '', denomination: '', community: '', sub_community: '', ethnicity: '', mother_tongue: '', marital_status: '', education: '', occupation: '' };

export function Discovery({ mode = 'discover' }) {
  const [filters, setFilters] = useState(emptyFilters);
  const [profiles, setProfiles] = useState([]);
  const [selected, setSelected] = useState(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [reportOpen, setReportOpen] = useState(false);
  const [reportReason, setReportReason] = useState('fake_identity');
  const [reportDetails, setReportDetails] = useState('');

  async function load(nextFilters = filters) {
    setLoading(true); setMessage(''); setError('');
    try {
      const page = mode === 'saved' ? await getFavourites() : await getMatches(nextFilters);
      setProfiles(page.data || []);
    } catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(mode === 'saved' ? {} : filters); }, [mode]);

  async function view(profile) {
    setMessage(''); setError('');
    try { setSelected(await getMatch(profile.id)); setReportOpen(false); setReportDetails(''); }
    catch (requestError) { setError(requestError.message); }
  }

  async function favourite(profile) {
    try {
      if (profile.is_favourite) await removeFavourite(profile.id); else await saveFavourite(profile.id);
      setProfiles((items) => mode === 'saved' && profile.is_favourite ? items.filter((item) => item.id !== profile.id) : items.map((item) => item.id === profile.id ? { ...item, is_favourite: !item.is_favourite } : item));
      if (selected?.id === profile.id) setSelected({ ...selected, is_favourite: !profile.is_favourite });
    } catch (requestError) { setError(requestError.message); }
  }

  async function interest(profile) {
    try { setError(''); await sendInterest(profile.user_id); setMessage(`Interest sent to ${profile.display_name}.`); }
    catch (requestError) { setError(requestError.message); }
  }

  async function block(profile) {
    if (!window.confirm(`Block ${profile.display_name}? You will no longer see or contact each other.`)) return;
    try {
      setError(''); await blockMember(profile.user_id, 'Blocked from profile'); setSelected(null); setMessage('Member blocked.'); await load();
    } catch (requestError) { setError(requestError.message); }
  }

  async function report(profile) {
    try { setError(''); await reportMember(profile.user_id, reportReason, reportDetails.trim() || null); setMessage('Your confidential report was submitted.'); setReportOpen(false); setReportDetails(''); }
    catch (requestError) { setError(requestError.message); }
  }

  async function askForPhotos(profile) {
    try {
      setError('');
      await requestPhotoAccess(profile.user_id);
      setSelected({ ...profile, private_photo_access_status: 'pending' });
      setMessage(`Private photo request sent to ${profile.display_name}.`);
    } catch (requestError) { setError(requestError.message); }
  }

  function search(event) { event.preventDefault(); load(filters); }

  return <section className="discovery-page">
    <div className="discovery-heading"><div><span className="kicker">{mode === 'saved' ? 'Your shortlist' : 'Member discovery'}</span><h1>{mode === 'saved' ? 'Saved profiles' : 'Find a meaningful match'}</h1></div><p>{mode === 'saved' ? 'Profiles you saved for a thoughtful second look.' : 'Search approved, active members and connect when a profile feels right.'}</p></div>
    {mode === 'discover' && <form className="match-filters" onSubmit={search}>
      <select aria-label="Gender" value={filters.gender} onChange={(e) => setFilters({ ...filters, gender: e.target.value })}><option value="">Any gender</option><option value="female">Women</option><option value="male">Men</option><option value="other">Other</option></select>
      <input aria-label="Minimum age" type="number" min="18" max="100" placeholder="Min age" value={filters.min_age} onChange={(e) => setFilters({ ...filters, min_age: e.target.value })} />
      <input aria-label="Maximum age" type="number" min="18" max="100" placeholder="Max age" value={filters.max_age} onChange={(e) => setFilters({ ...filters, max_age: e.target.value })} />
      <input aria-label="Country" placeholder="Country" value={filters.country} onChange={(e) => setFilters({ ...filters, country: e.target.value })} />
      <input aria-label="City" placeholder="City" value={filters.city} onChange={(e) => setFilters({ ...filters, city: e.target.value })} />
      <input list="discovery-religions" aria-label="Religion" placeholder="Religion" value={filters.religion} onChange={(e) => setFilters({ ...filters, religion: e.target.value })} />
      <input list="discovery-denominations" aria-label="Sect or denomination" placeholder="Sect or denomination" value={filters.denomination} onChange={(e) => setFilters({ ...filters, denomination: e.target.value })} />
      <input list="discovery-communities" aria-label="Community or caste" placeholder="Community or caste" value={filters.community} onChange={(e) => setFilters({ ...filters, community: e.target.value })} />
      <input aria-label="Sub-community or sub-caste" placeholder="Sub-community or sub-caste" value={filters.sub_community} onChange={(e) => setFilters({ ...filters, sub_community: e.target.value })} />
      <input list="discovery-ethnicities" aria-label="Ethnic background" placeholder="Ethnic background" value={filters.ethnicity} onChange={(e) => setFilters({ ...filters, ethnicity: e.target.value })} />
      <input aria-label="Language" placeholder="Mother tongue" value={filters.mother_tongue} onChange={(e) => setFilters({ ...filters, mother_tongue: e.target.value })} />
      <input aria-label="Occupation" placeholder="Occupation" value={filters.occupation} onChange={(e) => setFilters({ ...filters, occupation: e.target.value })} />
      <button className="btn btn-primary">Search profiles</button>
      <button type="button" className="clear-filters" onClick={() => { setFilters(emptyFilters); load(emptyFilters); }}>Clear</button>
      <SuggestionList id="discovery-religions" values={RELIGIONS} />
      <SuggestionList id="discovery-denominations" values={DENOMINATIONS} />
      <SuggestionList id="discovery-communities" values={COMMUNITIES} />
      <SuggestionList id="discovery-ethnicities" values={ETHNICITIES} />
    </form>}
    {message && <div className="auth-notice success">{message}</div>}
    {error && <div className="auth-notice error">{error}</div>}
    {loading ? <p className="matches-empty">Loading approved profiles…</p> : profiles.length === 0 ? <p className="matches-empty">{mode === 'saved' ? 'You have not saved any available profiles yet.' : 'No profiles match these filters. Try a broader search.'}</p> : <div className="match-grid">{profiles.map((profile) => <article className="match-card" key={profile.id}>
      <button className={`save-match ${profile.is_favourite ? 'saved' : ''}`} aria-label={profile.is_favourite ? 'Remove from saved profiles' : 'Save profile'} onClick={() => favourite(profile)}>♡</button>
      <button className="match-photo" onClick={() => view(profile)}><PrivateImage photo={profile.primary_photo} alt={`${profile.display_name}'s approved profile`} /></button>
      <div className="match-copy"><h3>{profile.display_name}, {profile.age}</h3><p>{[profile.city, profile.country].filter(Boolean).join(', ')} · {profile.occupation || 'Occupation not listed'}</p><small>{[profile.religion, profile.denomination, profile.community, profile.mother_tongue].filter(Boolean).join(' · ')}</small>{profile.match_reasons?.length > 0 && <span className="match-reason">✓ {profile.match_reasons[0]}</span>}<div><button onClick={() => view(profile)}>View profile</button><button className="interest-button" onClick={() => interest(profile)}>Send interest</button></div></div>
    </article>)}</div>}
      {selected && <div className="profile-modal" role="dialog" aria-modal="true"><article><button className="modal-close" onClick={() => setSelected(null)}>×</button><PrivateImage photo={selected.primary_photo} alt={`${selected.display_name}'s approved profile`} /><div className="profile-detail"><span className="kicker">{selected.profile_code} · {selected.verification_status === 'identity_verified' ? 'Identity verified' : selected.verification_status === 'reviewed' ? 'Profile reviewed' : 'Email verified'}</span><h2>{selected.display_name}, {selected.age}</h2><p>{[selected.city, selected.state, selected.country].filter(Boolean).join(', ')}</p>{selected.photos?.length > 1 && <div className="profile-gallery">{selected.photos.map((photo) => <PrivateImage key={photo.id} photo={photo} alt={`${selected.display_name}'s approved profile`} />)}</div>}{selected.private_photo_count > 0 && selected.private_photo_access_status !== 'approved' && <div className="private-photo-request"><strong>{selected.private_photo_count} private {selected.private_photo_count === 1 ? 'photo' : 'photos'}</strong><p>The member decides who may view these photos.</p><button disabled={selected.private_photo_access_status === 'pending'} onClick={() => askForPhotos(selected)}>{selected.private_photo_access_status === 'pending' ? 'Request pending' : 'Request photo access'}</button></div>}<dl><div><dt>Education</dt><dd>{selected.education || 'Not listed'}</dd></div><div><dt>Occupation</dt><dd>{selected.occupation || 'Not listed'}</dd></div><div><dt>Faith & community</dt><dd>{[selected.religion, selected.denomination, selected.community, selected.sub_community].filter(Boolean).join(' · ') || 'Not listed'}</dd></div><div><dt>Ethnic background</dt><dd>{selected.ethnicity || 'Not listed'}</dd></div><div><dt>Language</dt><dd>{selected.mother_tongue || 'Not listed'}</dd></div></dl><h3>About</h3><p>{selected.about_me || 'This member has not added an introduction yet.'}</p>{selected.match_reasons?.map((reason) => <span className="detail-reason" key={reason}>✓ {reason}</span>)}<div className="detail-actions"><button onClick={() => favourite(selected)}>{selected.is_favourite ? 'Remove saved' : 'Save profile'}</button><button className="btn btn-primary" onClick={() => interest(selected)}>Send interest</button><button className="safety-action" onClick={() => setReportOpen(!reportOpen)}>Report</button><button className="safety-action danger" onClick={() => block(selected)}>Block</button></div>{reportOpen && <div className="report-form"><h3>Confidential report</h3><p>Choose the closest reason and share only the details needed for our safety team to investigate.</p><label>Reason<select value={reportReason} onChange={(event) => setReportReason(event.target.value)}><option value="fake_identity">Fake identity</option><option value="commercial_use">Marriage bureau or commercial use</option><option value="scam">Scam or financial request</option><option value="harassment">Harassment</option><option value="inappropriate_content">Inappropriate content</option><option value="underage_concern">Underage concern</option><option value="other">Other safety concern</option></select></label><label>Details<textarea maxLength="1000" value={reportDetails} onChange={(event) => setReportDetails(event.target.value)} placeholder="What happened?" /></label><button className="btn btn-primary" onClick={() => report(selected)}>Submit confidential report</button></div>}</div></article></div>}
  </section>;
}
