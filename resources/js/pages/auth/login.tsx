import { Head, Link, useForm } from '@inertiajs/react';
import { AuthLayout, Field, Errors } from '../../components/ui';
export default function Login() {
  const form = useForm({ email: '', password: '', remember: false });
  return <AuthLayout title="Welkom terug." description="Log in om verder te gaan in jouw ruimte."><Head title="Inloggen"/><form onSubmit={(e) => { e.preventDefault(); form.post('/login', { onFinish: () => form.reset('password') }); }}><Field label="E-mailadres" name="email" type="email" autoComplete="username" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required autoFocus/><Field label="Wachtwoord" name="password" type="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required/><Errors errors={form.errors}/><Link className="text-link small" href="/forgot-password">Wachtwoord vergeten?</Link><button className="button wide" disabled={form.processing}>Inloggen →</button></form><p className="small">Nog geen account? <Link href="/register">Maak een account</Link></p></AuthLayout>;
}
