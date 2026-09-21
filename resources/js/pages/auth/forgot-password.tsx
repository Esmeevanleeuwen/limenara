import { Head, useForm } from '@inertiajs/react';
import { AuthLayout, Field, Errors } from '../../components/ui';
export default function ForgotPassword({ status }: { status?: string }) {
  const form = useForm({ email: '' });
  return <AuthLayout title="Opnieuw toegang." description="Vraag een link aan om je wachtwoord te herstellen."><Head title="Wachtwoord vergeten"/>{status && <p className="notice success" role="status">{status}</p>}<form onSubmit={(e) => { e.preventDefault(); form.post('/forgot-password'); }}><Field label="E-mailadres" name="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required/><Errors errors={form.errors}/><button className="button wide" disabled={form.processing}>Stuur herstellink</button></form></AuthLayout>;
}
