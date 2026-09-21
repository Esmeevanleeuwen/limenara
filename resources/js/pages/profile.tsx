import { Head, Link } from '@inertiajs/react';
import { PublicLayout } from '../components/ui';
import type { Profile } from '../types';
export default function PublicProfile({ profile }: { profile: Profile }) {
  return <PublicLayout><Head title={profile.display_name}/><article className="prose-page page-width"><Link href="/makers" className="text-link">← Alle makers</Link><p className="eyebrow">OPENBAAR MAKERSPROFIEL</p><h1>{profile.display_name}</h1><p className="lead">{profile.headline}</p><div className="tags">{profile.specialties?.map((s) => <span key={s}>{s}</span>)}</div><p className="notice">Zelf opgegeven expertise. Kwalificaties zijn in deze versie niet geverifieerd; dit profiel geeft geen behandelbevoegdheid aan.</p><h2>Over mij</h2><p className="preserve">{profile.biography || 'Deze maker heeft nog geen introductie toegevoegd.'}</p><h2>Achtergrond en opleiding</h2><p className="preserve">{profile.education || 'Nog geen achtergrond toegevoegd.'}</p></article></PublicLayout>;
}
