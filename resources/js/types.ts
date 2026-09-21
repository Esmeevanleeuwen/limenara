export type CurrentUser = { id: number; name: string; email: string; verified: boolean; permissions: string[] };
export type Shared = { [key: string]: unknown; auth: { user: CurrentUser | null }; flash: { success?: string; error?: string } };
export type Paginated<T> = { data: T[]; prev_page_url: string | null; next_page_url: string | null; current_page: number; last_page: number };
export type Lesson = { title: string; type: 'text' | 'reflection'; body: string };
export type ProgramDraft = { id: number; title: string; summary: string; goals: string | null; estimated_minutes: number; lessons: Lesson[]; status: 'draft' | 'review' | 'published'; revision: number };
export type Profile = { public_id: string; display_name: string; headline: string; biography?: string | null; education?: string | null; specialties: string[] | null; verification_status?: string; is_public?: boolean };
