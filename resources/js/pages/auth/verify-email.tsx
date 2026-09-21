import { Head, Link, router, useForm } from '@inertiajs/react';
import { AuthLayout, Errors } from '../../components/ui';
export default function VerifyEmail({ status, email, localMailbox }: { status?: string; email?: string; localMailbox?: string | null }) {
  const form = useForm({});
  return <AuthLayout title="Nog één stap." description="Bevestig jouw e-mailadres. Daarna kun je verder met wat je wilde doen."><Head title="E-mail bevestigen"/><p>De bevestigingsmail is aangemaakt voor <strong>{email}</strong>.</p>
    {localMailbox ? <div className="notice subtle"><strong>Je werkt lokaal met een testinbox.</strong><p>De mail komt niet in je echte mailbox. Open Mailpit en klik daar op ‘E-mailadres bevestigen’. Gebruik dezelfde browser waarin je bent ingelogd.</p><a className="button secondary" href={localMailbox} target="_blank" rel="noreferrer">Open de testinbox →</a></div> : <p>Open je e-mail en klik op de bevestigingslink. Kijk eventueel in je ongewenste e-mail.</p>}
    {status && <p role="status" className="notice success">Een nieuwe bevestigingsmail is verstuurd.</p>}
    <Errors errors={form.errors}/><button className="button wide" disabled={form.processing} onClick={() => form.post('/email/verification-notification')}>Stuur opnieuw</button><button className="button secondary wide" onClick={() => router.visit('/dashboard')}>Ik heb bevestigd — verdergaan</button><Link href="/logout" method="post" as="button" className="text-link">Uitloggen</Link>
  </AuthLayout>;
}
