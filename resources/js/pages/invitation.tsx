import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { AuthLayout, Errors } from '../components/ui';
import type { Shared } from '../types';
export default function Invitation({ emailHint, matches }: { emailHint: string; matches: boolean }) {
  const { auth } = usePage<Shared>().props;
  const form = useForm({});
  return <AuthLayout title="Welkom als maker." description={`Deze persoonlijke uitnodiging is voor ${emailHint}.`}><Head title="Medewerkersuitnodiging"/><p>Na acceptatie kun je jouw profiel invullen en educatieve programma’s voorbereiden. Behandelbevoegdheden worden hiermee niet toegekend.</p><Errors errors={form.errors}/>{!auth.user ? <div className="actions"><Link href="/register" className="button">Account maken</Link><Link href="/login" className="button secondary">Ik heb al een account</Link></div> : !auth.user.verified ? <Link href="/email/verify" className="button">Bevestig eerst je e-mailadres</Link> : matches ? <button className="button wide" onClick={() => form.post('/uitnodiging')} disabled={form.processing}>Uitnodiging accepteren</button> : <p className="notice">Je bent ingelogd met een ander e-mailadres. Log uit en gebruik het uitgenodigde account.</p>}{auth.user && <div className="actions"><Link href="/logout" method="post" as="button" className="text-link">Uitloggen</Link>{auth.user.verified && <Link href="/uitnodiging/overslaan" method="post" as="button" className="text-link">Nu overslaan</Link>}</div>}</AuthLayout>;
}
