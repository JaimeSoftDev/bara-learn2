export type Role = 'student' | 'teacher' | 'admin'

export interface User {
  id: number
  name: string
  email?: string
  role: Role
  avatar_url: string | null
  headline: string | null
  bio: string | null
}

export interface Category {
  id: number
  name: string
  slug: string
  icon: string | null
  courses_count?: number
}

export interface CourseSummary {
  id: number
  title: string
  slug: string
  subtitle: string | null
  thumbnail_url: string | null
  level: 'beginner' | 'intermediate' | 'advanced'
  language: string
  price: number
  is_free: boolean
  status: 'draft' | 'published'
  average_rating: number
  reviews_count: number
  students_count: number
  category: { id: number; name: string; slug: string } | null
  teacher: { id: number; name: string; avatar_url: string | null }
}

export interface Lesson {
  id: number
  section_id: number
  title: string
  position: number
  duration_seconds: number | null
  is_preview: boolean
  locked: boolean
  completed: boolean
  youtube_video_id: string | null
  content: string | null
}

export interface QuizSummary {
  id: number
  title: string
  passing_score: number
  is_final_exam: boolean
  questions_count: number
  passed: boolean
  best_score: number | null
}

export interface QuizOption {
  id: number
  option_text: string
  is_correct?: boolean
}

export interface QuizQuestion {
  id: number
  question: string
  position: number
  options: QuizOption[]
}

export interface Quiz {
  id: number
  course_id: number
  section_id: number | null
  is_final_exam: boolean
  title: string
  passing_score: number
  questions_count?: number
  questions: QuizQuestion[]
}

export interface Section {
  id: number
  title: string
  position: number
  lessons: Lesson[]
  quiz?: QuizSummary | null
}

export interface Course extends CourseSummary {
  description: string | null
  requirements: string[]
  what_you_will_learn: string[]
  lessons_count: number
  published_at: string | null
  is_enrolled: boolean
  is_owner: boolean
  teacher: {
    id: number
    name: string
    avatar_url: string | null
    headline: string | null
    bio: string | null
  }
  sections: Section[]
  final_exam?: QuizSummary | null
}

export interface Review {
  id: number
  rating: number
  comment: string | null
  created_at: string
  user: { id: number; name: string; avatar_url: string | null }
}

export interface Answer {
  id: number
  body: string
  is_instructor_answer: boolean
  created_at: string
  user: { id: number; name: string; avatar_url: string | null }
}

export interface Question {
  id: number
  title: string
  body: string
  created_at: string
  user: { id: number; name: string; avatar_url: string | null }
  answers: Answer[]
}

export interface Enrollment {
  id: number
  source: 'stripe' | 'cash' | 'free'
  price_paid: number
  enrolled_at: string
  progress_percent?: number
  course: CourseSummary
  user?: { id: number; name: string; email: string }
  granted_by?: { id: number; name: string } | null
}

export interface Order {
  id: number
  amount: number
  currency: string
  payment_method: 'stripe' | 'cash'
  status: 'pending' | 'paid' | 'failed' | 'refunded'
  notes: string | null
  paid_at: string | null
  created_at: string
  course: { id: number; title: string; slug: string }
  user: { id: number; name: string; email: string }
  created_by: { id: number; name: string } | null
}

export interface TutoringMessage {
  id: number
  body: string
  sender: { id: number; name: string; avatar_url: string | null }
  is_mine: boolean
  created_at: string
}

export interface TutoringThread {
  id: number
  course: { id: number; title: string; slug: string }
  student: { id: number; name: string; avatar_url: string | null }
  questions_used: number
  questions_limit: number
  questions_remaining: number
  unread_count: number
  last_message: { body: string; sender_id: number; created_at: string } | null
  messages: TutoringMessage[]
  updated_at: string
}

export interface Certificate {
  id: number
  code: string
  issued_at: string
  course: { id: number; title: string; slug: string }
}

export interface Paginated<T> {
  data: T[]
  meta?: {
    current_page: number
    last_page: number
    total: number
    per_page: number
  }
  links?: unknown
}
