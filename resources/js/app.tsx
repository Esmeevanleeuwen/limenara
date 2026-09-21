import { createInertiaApp } from '@inertiajs/react';

void createInertiaApp({
  title: (title) => title ? `${title} · Limenora` : 'Limenora',
  strictMode: true,
  progress: { color: '#0f2747' },
});
