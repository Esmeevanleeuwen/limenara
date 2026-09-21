import { Head, Link, useForm } from '@inertiajs/react';
import { AuthLayout, Errors } from '../../components/ui';
export default function VerifyEmail({ status }: { status?: string }) {
  const form = useForm({});
  return <AuthLayout title="Nog één stap." description="Open de bevestigingsmail om jouw e-mailadres te bevestigen."><Head title="E-mail bevestigen"/><p>Werk je lokaal? Dan komt de e-mail in Mailpit terecht, niet in je echte inbox.</p>{status && <p role="status" className="notice success">Een nieuwe bevestigingsmail is verstuurd.</p>}<Errors errors={form.errors}/><button className="button wide" disabled={form.processing} onClick={() => form.post('/email/verification-notification')}>Stuur opnieuw</button><Link href="/logout" method="post" as="button" className="text-link">Uitloggen</Link></AuthLayout>;
}
