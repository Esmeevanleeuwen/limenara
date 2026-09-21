import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, BookOpen, LayoutDashboard, LogOut, ShieldCheck, Users, UserRound } from 'lucide-react';
import type { InputHTMLAttributes, ReactNode } from 'react';
import type { Shared } from '../types';

export function Wave({ className = '' }: { className?: string }) {
  return <svg className={className} viewBox="0 0 320 180" fill="none" aria-hidden="true"><g strokeWidth="3.2" strokeLinecap="round"><path d="M12 110C72 146 99 1 147 32S239 131 307 93M12 121C73 154 100 20 148 47S239 140 307 104M12 132C73 165 103 40 150 63S239 146 307 115" stroke="currentColor"/><path d="M12 143C74 176 108 61 153 80S238 151 307 126M12 154C75 183 112 82 156 97S237 157 307 137M12 165C77 191 116 104 159 115S237 165 307 148" stroke="#8ca695"/></g></svg>;
}
export function Logo() { return <span className="brand"><Wave/><span>Limenora<small>ruimte om te begrijpen</small></span></span>; }
export function Header() {
  const { auth } = usePage<Shared>().props;
  return <header className="site-header"><Link href="/" aria-label="Limenora homepage"><Logo/></Link><nav aria-label="Hoofdnavigatie"><Link href="/programmas">Programma’s</Link><Link href="/makers">Onze makers</Link><Link href="/privacy">Jouw gegevens</Link></nav><div className="header-actions">{auth.user ? <Link className="button" href="/dashboard">Mijn ruimte <ArrowRight size={16}/></Link> : <><Link className="login-link" href="/login">Inloggen</Link><Link className="button" href="/register">Begin hier <ArrowRight size={16}/></Link></>}</div></header>;
}
export function Flash() {
  const { flash } = usePage<Shared>().props;
  return <>{flash.success && <p className="notice success" role="status">{flash.success}</p>}{flash.error && <p className="notice" role="alert">{flash.error}</p>}</>;
}
export function PublicLayout({ children }: { children: ReactNode }) {
  return <><a className="skip" href="#inhoud">Naar inhoud</a><Header/><div className="dev-label">Ontwikkelversie · gebruik alleen testgegevens · geen behandeling of crisishulp</div><main id="inhoud">{children}</main><footer className="site-footer"><Logo/><p>Een platform vanuit Meridian & Phosphoros.</p><Link href="/privacy">Over deze ontwikkelversie</Link></footer></>;
}
export function Workspace({ children, title, subtitle }: { children: ReactNode; title: string; subtitle?: string }) {
  const { auth } = usePage<Shared>().props;
  const can = (p: string) => auth.user?.permissions.includes(p);
  return <PublicLayout><div className="workspace"><aside className="sidebar"><small>JOUW RUIMTE</small><Link href="/dashboard"><LayoutDashboard size={18}/> Overzicht</Link><Link href="/programmas"><BookOpen size={18}/> Programma’s ontdekken</Link><Link href="/settings/security"><ShieldCheck size={18}/> Beveiliging</Link>{can('programs.create') && <><small>WERKOMGEVING</small><Link href="/werk/profiel"><UserRound size={18}/> Mijn professionele profiel</Link><Link href="/werk/programmas"><BookOpen size={18}/> Programma’s maken</Link></>}{can('staff.invite') && <><small>BEHEER</small><Link href="/beheer/medewerkers"><Users size={18}/> Medewerkers</Link><Link href="/beheer/beoordelingen"><ShieldCheck size={18}/> Beoordelingen</Link></>}<Link href="/logout" method="post" as="button" className="logout"><LogOut size={17}/> Uitloggen</Link></aside><section className="workspace-main"><div className="section-heading"><p className="eyebrow">LIMENORA / MIJN RUIMTE</p><h1>{title}</h1>{subtitle && <p className="muted">{subtitle}</p>}</div><Flash/>{children}</section></div></PublicLayout>;
}
export function AuthLayout({ children, title, description }: { children: ReactNode; title: string; description?: string }) {
  return <div className="auth-layout"><section className="auth-form"><Link href="/"><Logo/></Link><div className="auth-inner"><p className="eyebrow">OP JOUW MANIER</p><h1>{title}</h1>{description && <p className="muted">{description}</p>}<Flash/>{children}<p className="fineprint">Ontwikkelversie. Gebruik geen echte cliëntgegevens.</p><Link href="/" className="text-link"><ArrowLeft size={15}/> Terug naar Limenora</Link></div></section><aside className="auth-aside"><Wave/><p>Je hoeft niet alles<br/>al te begrijpen<br/><em>om te beginnen.</em></p><span>Limenora · Meridian & Phosphoros</span></aside></div>;
}
export function Field({ label, name, error, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string; name: string; error?: string }) {
  return <div className="field"><label htmlFor={name}>{label}</label><input id={name} name={name} aria-invalid={!!error} aria-describedby={error ? `${name}-error` : undefined} {...props}/>{error && <small id={`${name}-error`} className="error">{error}</small>}</div>;
}
export function Errors({ errors }: { errors: Record<string, string | undefined> }) {
  const values = [...new Set(Object.values(errors).filter(Boolean))];
  return values.length ? <div role="alert" className="notice">{values.map((e) => <p key={e}>{e}</p>)}</div> : null;
}
export function Empty({ title, children }: { title: string; children: ReactNode }) { return <div className="empty"><span className="empty-icon"><BookOpen/></span><h2>{title}</h2><p className="muted">{children}</p></div>; }
export function Pagination({ prev, next }: { prev: string | null; next: string | null }) { return <nav className="pagination" aria-label="Pagina’s">{prev && <Link href={prev}>← Vorige</Link>}{next && <Link href={next}>Volgende →</Link>}</nav>; }
export function Status({ value }: { value: string }) { return <span className={`status ${value}`}>{({ draft: 'Concept', review: 'Ter beoordeling', published: 'Gepubliceerd' } as Record<string, string>)[value] ?? value}</span>; }
