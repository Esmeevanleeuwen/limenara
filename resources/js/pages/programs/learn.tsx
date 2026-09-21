import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Check, ChevronRight } from 'lucide-react';
import { Workspace, Errors } from '../../components/ui';
import type { Lesson } from '../../types';
type Props = { enrollment: { id: number; completed_lessons: number[] }; version: { title: string; number: number; lessons: Lesson[] } };
export default function Learn({ enrollment, version }: Props) {
  const [active, setActive] = useState(0);
  const form = useForm({ lesson: 0, completed: true });
  const lesson = version.lessons[active];
  const done = enrollment.completed_lessons.includes(active);
  return <Workspace title={version.title} subtitle={`Jouw vaste programmaversie ${version.number}. Neem de tijd die je nodig hebt.`}><Head title={version.title}/><div className="learning-grid"><nav className="lesson-nav" aria-label="Lessen">{version.lessons.map((l, i) => <button key={i} className={active === i ? 'selected' : ''} aria-current={active === i ? 'step' : undefined} onClick={() => setActive(i)}><span>{enrollment.completed_lessons.includes(i) ? <Check size={17}/> : i + 1}</span>{l.title}<ChevronRight size={14}/></button>)}</nav><article className="card lesson-content"><p className="eyebrow">LES {active + 1} / {version.lessons.length} · {lesson.type === 'reflection' ? 'REFLECTIE' : 'LEZEN'}</p><h2>{lesson.title}</h2><div className="preserve">{lesson.body}</div>{lesson.type === 'reflection' && <p className="notice subtle">Je kunt deze vraag voor jezelf overdenken. Persoonlijke antwoorden worden in deze versie niet ingevoerd of opgeslagen.</p>}<Errors errors={form.errors}/><button className="button" disabled={form.processing} onClick={() => form.transform(() => ({ lesson: active, completed: !done })).put(`/mijn-programmas/${enrollment.id}/voortgang`, { preserveScroll: true })}>{done ? 'Markeer als nog niet afgerond' : 'Les afronden'}</button>{active < version.lessons.length - 1 && <button className="text-link next-lesson" onClick={() => setActive(active + 1)}>Volgende les →</button>}</article></div></Workspace>;
}
