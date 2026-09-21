import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AuthLayout, Field, Errors } from '../../components/ui';
export default function TwoFactorChallenge() {
  const [recovery, setRecovery] = useState(false);
  const form = useForm({ code: '', recovery_code: '' });
  return <AuthLayout title="Dit ben jij." description="Gebruik je authenticator-app of een herstelcode."><Head title="Tweestapsverificatie"/><form onSubmit={(e) => { e.preventDefault(); form.post('/two-factor-challenge', { onFinish: () => form.reset() }); }}>{recovery ? <Field label="Herstelcode" name="recovery_code" autoComplete="one-time-code" value={form.data.recovery_code} onChange={(e) => form.setData('recovery_code', e.target.value)} required/> : <Field label="Verificatiecode" name="code" inputMode="numeric" autoComplete="one-time-code" value={form.data.code} onChange={(e) => form.setData('code', e.target.value)} required/>}<Errors errors={form.errors}/><button className="button wide" disabled={form.processing}>Inloggen</button></form><button className="text-link" onClick={() => { form.reset(); setRecovery(!recovery); }}>{recovery ? 'Gebruik de authenticator-app' : 'Gebruik een herstelcode'}</button></AuthLayout>;
}
