import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior() {
    return { top: 0 }
  },
  routes: [
    {
      path: '/',
      component: () => import('@/layouts/DefaultLayout.vue'),
      children: [
        { path: '', name: 'home', component: () => import('@/views/HomeView.vue') },
        { path: 'courses', name: 'courses', component: () => import('@/views/CoursesView.vue') },
        {
          path: 'courses/:slug',
          name: 'course-detail',
          component: () => import('@/views/CourseDetailView.vue'),
          props: true,
        },
        {
          path: 'learn/:slug',
          name: 'learn',
          component: () => import('@/views/LearnView.vue'),
          props: true,
          meta: { requiresAuth: true },
        },
        { path: 'login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guestOnly: true } },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { guestOnly: true },
        },
        {
          path: 'verify-certificate/:code?',
          name: 'verify-certificate',
          component: () => import('@/views/VerifyCertificateView.vue'),
          props: true,
        },
      ],
    },
    {
      path: '/',
      component: () => import('@/layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'account/profile',
          name: 'account-profile',
          component: () => import('@/views/ProfileView.vue'),
        },
        // Student
        {
          path: 'my-courses',
          name: 'my-courses',
          component: () => import('@/views/student/MyCoursesView.vue'),
          meta: { role: 'student' },
        },
        {
          path: 'wishlist',
          name: 'wishlist',
          component: () => import('@/views/student/WishlistView.vue'),
          meta: { role: 'student' },
        },
        {
          path: 'account/certificates',
          name: 'certificates',
          component: () => import('@/views/student/CertificatesView.vue'),
          meta: { role: 'student' },
        },
        // Teacher
        {
          path: 'teacher',
          name: 'teacher-overview',
          component: () => import('@/views/teacher/TeacherOverviewView.vue'),
          meta: { role: 'teacher' },
        },
        {
          path: 'teacher/courses',
          name: 'teacher-courses',
          component: () => import('@/views/teacher/TeacherCoursesView.vue'),
          meta: { role: 'teacher' },
        },
        {
          path: 'teacher/courses/new',
          name: 'teacher-course-new',
          component: () => import('@/views/teacher/CourseFormView.vue'),
          meta: { role: 'teacher' },
        },
        {
          path: 'teacher/courses/:slug/edit',
          name: 'teacher-course-edit',
          component: () => import('@/views/teacher/CourseEditorView.vue'),
          props: true,
          meta: { role: 'teacher' },
        },
        {
          path: 'teacher/courses/:slug/students',
          name: 'teacher-course-students',
          component: () => import('@/views/teacher/CourseStudentsView.vue'),
          props: true,
          meta: { role: 'teacher' },
        },
        {
          path: 'teacher/orders',
          name: 'teacher-orders',
          component: () => import('@/views/teacher/TeacherOrdersView.vue'),
          meta: { role: 'teacher' },
        },
        {
          path: 'teacher/tutoring',
          name: 'teacher-tutoring',
          component: () => import('@/views/teacher/TeacherTutoringView.vue'),
          meta: { role: 'teacher' },
        },
        // Admin
        {
          path: 'admin',
          name: 'admin-overview',
          component: () => import('@/views/admin/AdminOverviewView.vue'),
          meta: { role: 'admin' },
        },
        {
          path: 'admin/users',
          name: 'admin-users',
          component: () => import('@/views/admin/AdminUsersView.vue'),
          meta: { role: 'admin' },
        },
        {
          path: 'admin/courses',
          name: 'admin-courses',
          component: () => import('@/views/admin/AdminCoursesView.vue'),
          meta: { role: 'admin' },
        },
        {
          path: 'admin/categories',
          name: 'admin-categories',
          component: () => import('@/views/admin/AdminCategoriesView.vue'),
          meta: { role: 'admin' },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.ready) {
    await auth.fetchMe()
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'home' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  const requiredRole = to.meta.role as string | undefined
  if (requiredRole && auth.user?.role !== requiredRole) {
    return { name: 'home' }
  }

  return true
})

export default router
