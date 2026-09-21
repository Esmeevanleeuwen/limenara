import { Head, useForm } from '@inertiajs/react';
import { AuthLayout, Field, Errors } from '../../components/ui';
export default function ConfirmPassword() {
  const form = useForm({ password: '' });
  return <AuthLayout title="Even controleren." description="Bevestig je wachtwoord voordat je beveiligingsinstellingen bekijkt."><Head title="Wachtwoord bevestigen"/><form onSubmit={(e) => { e.preventDefault(); form.post('/user/confirm-password', { onFinish: () => form.reset() }); }}><Field label="Wachtwoord" name="password" type="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required/><Errors errors={form.errors}/><button className="button wide" disabled={form.processing}>Bevestigen</button></form></AuthLayout>;
}
