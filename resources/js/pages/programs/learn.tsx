import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Check, ChevronRight, Pause, Play, LockKeyhole } from 'lucide-react';
import { Workspace, Errors } from '../../components/ui';
import { LessonBlock } from '../../components/lesson-block';
import type { Lesson, ResponseEntry } from '../../types';

type Props = {
  enrollment: { id: number; completed_lessons: number[]; is_paused: boolean; completed_at: string | null };
  version: { title: string; number: number; lessons: Lesson[] };
  responses: ResponseEntry[]; maker: { name: string; can_receive: boolean };
};
function Reflection({ enrollment, index, response, maker, paused, onDirty }: { enrollment: number; index: number; response?: ResponseEntry; maker: Props['maker']; paused: boolean; onDirty: (dirty: boolean) => void }) {
  const form = useForm({ answer: response?.answer ?? '', share: maker.can_receive && (response?.shared ?? false), revision: response?.revision ?? 0 });
  useEffect(() => { onDirty(form.isDirty); return () => onDirty(false); }, [form.isDirty, onDirty]);
  return <section className="response-editor"><h3><LockKeyhole size={18}/> Jouw eigen notitie</h3><p className="fineprint">Vrijwillig. Je kunt altijd zonder antwoord verder. Gebruik in deze ontwikkelversie uitsluitend verzonnen testinhoud.</p>
    <form onSubmit={e => { e.preventDefault(); form.put(`/mijn-programmas/${enrollment}/antwoorden/${index}`, { preserveScroll: true }); }}>
      <fieldset disabled={paused || form.processing}><div className="field"><label htmlFor="answer">Wat wil je voor jezelf vastleggen?</label><textarea id="answer" rows={6} maxLength={4000} value={form.data.answer} onChange={e => form.setData('answer', e.target.value)} required/></div>
      {maker.can_receive && <label className="check-row"><input type="checkbox" checked={form.data.share} onChange={e => form.setData('share', e.target.checked)}/>Deel alleen dit antwoord met {maker.name}</label>}
      <p className="fineprint">{form.data.share ? `Bij opslaan kan ${maker.name} dit antwoord, je schermnaam en de les zien. Niet je e-mailadres, overige privéantwoorden of totale voortgang. Er is geen gegarandeerde reactietijd en dit is geen crisischat.` : 'Alleen jij kunt dit antwoord via het platform lezen. Het wordt versleuteld opgeslagen; dit is geen end-to-endversleuteling.'}</p>
      <Errors errors={form.errors}/><button className="button secondary" disabled={form.processing}>{form.data.share ? 'Opslaan en delen' : 'Privé opslaan'}</button>
      </fieldset>
    </form>
    {response && <div className="actions response-controls">{response.shared && <button type="button" className="text-link" onClick={() => { if (window.confirm('Toegang voor de maker intrekken? Wat al gelezen of gekopieerd is kan niet worden teruggehaald.')) router.delete(`/mijn-programmas/${enrollment}/antwoorden/${index}/delen`, { preserveScroll: true }); }}>Delen intrekken</button>}<button type="button" className="text-link danger" onClick={() => { if (window.confirm('Dit testantwoord en de reactie definitief verwijderen?')) router.delete(`/mijn-programmas/${enrollment}/antwoorden/${index}`, { preserveScroll: true }); }}>Antwoord verwijderen</button></div>}
    {response?.feedback && <aside className="feedback-card"><p className="eyebrow">REACTIE VAN {maker.name}</p><p className="preserve">{response.feedback}</p><p className="fineprint">Deze reactie kan over een eerdere versie van je antwoord gaan. Een reactie is geen diagnose of behandelbesluit.</p></aside>}
  </section>;
}
export default function Learn({ enrollment, version, responses, maker }: Props) {
  const first = version.lessons.findIndex((_, i) => !enrollment.completed_lessons.includes(i));
  const [active, setActive] = useState(first < 0 ? 0 : first);
  const [dirty, setDirty] = useState(false);
  const form = useForm({ lesson: 0, completed: true });
  const pause = useForm({ is_paused: false });
  const lesson = version.lessons[active];
  const response = responses.find(r => r.lesson_index === active);
  const done = enrollment.completed_lessons.includes(active);
  useEffect(() => {
    const warn = (e: BeforeUnloadEvent) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } };
    window.addEventListener('beforeunload', warn); return () => window.removeEventListener('beforeunload', warn);
  }, [dirty]);
  function go(index: number) { if (!dirty || window.confirm('Je hebt een niet-opgeslagen antwoord. Toch naar een ander onderdeel gaan?')) { setDirty(false); setActive(index); } }
  function toggleCompletion() { form.transform(() => ({ lesson: active, completed: !done })); form.put(`/mijn-programmas/${enrollment.id}/voortgang`, { preserveScroll: true }); }
  function togglePause() {
    if (dirty && !window.confirm('Je hebt een niet-opgeslagen antwoord. Toch pauzeren of hervatten?')) return;
    pause.transform(() => ({ is_paused: !enrollment.is_paused })); pause.put(`/mijn-programmas/${enrollment.id}/pauze`, { preserveScroll: true });
  }
  return <Workspace title={version.title} subtitle={`Jouw vaste programmaversie ${version.number}. Geen deadline en geen verplichting om antwoorden te delen.`}>
    <Head title={version.title}/>
    <div className="learning-summary"><div><strong>{enrollment.completed_lessons.length} van {version.lessons.length} onderdelen doorlopen</strong><progress aria-label="Jouw voortgang" value={enrollment.completed_lessons.length} max={version.lessons.length}/></div><button className="button secondary" disabled={pause.processing} onClick={togglePause}>{enrollment.is_paused ? <Play size={16}/> : <Pause size={16}/>} {enrollment.is_paused ? 'Hervatten' : 'Pauzeren'}</button></div>
    {enrollment.is_paused && <p className="notice subtle">Je programma is gepauzeerd. Teruglezen, een gedeeld antwoord intrekken of verwijderen blijft mogelijk.</p>}
    {enrollment.completed_at && <p className="notice success">Alle onderdelen doorlopen. Dit is geen score voor je gezondheid of herstel. Je kunt rustig terugkijken.</p>}
    <Errors errors={pause.errors}/>
    <div className="learning-grid"><nav className="lesson-nav" aria-label="Lessen">{version.lessons.map((item, i) => <button key={i} className={active === i ? 'selected' : ''} aria-current={active === i ? 'step' : undefined} onClick={() => go(i)}><span>{enrollment.completed_lessons.includes(i) ? <Check size={17}/> : i + 1}</span>{item.title}<ChevronRight size={14}/></button>)}</nav>
      <article className="card lesson-content"><LessonBlock key={active} lesson={lesson}/>
        {['reflection', 'action'].includes(lesson.type) && <Reflection key={`${active}:${response?.revision ?? 0}`} enrollment={enrollment.id} index={active} response={response} maker={maker} paused={enrollment.is_paused} onDirty={setDirty}/>}
        <Errors errors={form.errors}/><div className="lesson-footer"><button className="button" disabled={form.processing || enrollment.is_paused} onClick={toggleCompletion}>{done ? 'Markeer als nog niet afgerond' : 'Onderdeel afronden'}</button>{active < version.lessons.length - 1 && <button className="text-link" onClick={() => go(active + 1)}>Volgende onderdeel →</button>}</div><p className="fineprint">Afronden betekent alleen dat je dit onderdeel hebt bekeken. Je hoeft geen antwoord of juiste quizkeuze op te slaan.</p>
      </article>
    </div>
  </Workspace>;
}
