import { useState } from 'react';
import type { Lesson } from '../types';

export const lessonLabels: Record<Lesson['type'], string> = {
  text: 'Lezen', reflection: 'Reflectie', quiz: 'Kennisvraag', checklist: 'Keuzelijst', action: 'Eigen stap',
};

/** Shared renderer: makers and reviewers see the same content as the participant. */
export function LessonBlock({ lesson }: { lesson: Lesson }) {
  const [answer, setAnswer] = useState<number | null>(null);
  return <>
    <p className="eyebrow">{lesson.module || 'Onderdeel'} · {lessonLabels[lesson.type]}{lesson.minutes ? ` · ${lesson.minutes} min` : ''}</p>
    <h2>{lesson.title || 'Nog geen titel'}</h2>
    <div className="preserve">{lesson.body}</div>
    {lesson.type === 'quiz' && <fieldset className="knowledge-question">
      <legend>{lesson.question || 'Jouw kennisvraag'}</legend>
      <p className="fineprint">Geen medische score. Je antwoord wordt niet opgeslagen of gedeeld en is niet nodig om verder te gaan.</p>
      <div className="choice-list">{(lesson.options ?? []).map((option, i) => <button type="button" className={`choice ${answer === i ? 'chosen' : ''}`} aria-pressed={answer === i} key={i} onClick={() => setAnswer(i)}>{option || `Optie ${i + 1}`}</button>)}</div>
      {answer !== null && <div className="notice subtle" role="status"><strong>{answer === lesson.correct_option ? 'Dit sluit aan bij de uitleg.' : 'Lees de toelichting; je mag opnieuw kiezen.'}</strong><p>{lesson.explanation}</p></div>}
    </fieldset>}
    {lesson.type === 'checklist' && <fieldset className="knowledge-question"><legend>Wat wil je verkennen?</legend><p className="fineprint">Vrijblijvend. Deze vinkjes verdwijnen wanneer je dit onderdeel verlaat.</p>{(lesson.items ?? []).map((item, i) => <label className="check-row" key={i}><input type="checkbox"/>{item}</label>)}</fieldset>}
  </>;
}
