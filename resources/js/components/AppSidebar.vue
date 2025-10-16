<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import * as Icons from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import type { Auth } from '@/types';

const { LayoutDashboard, Calendar, Activity, FileText, Receipt, CreditCard, Hospital, Stethoscope, Users, Building2, List, Wallet, PieChart, LogOut, Menu, ChevronDown, ChevronLeft, Settings } = Icons;

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);
const userType = computed(() => user.value.user_type as string);

interface SidebarMenuItem {
  name: string;
  path: string;
  icon: any;
  children?: SidebarMenuItem[];
}

const isCollapsed = ref(false);
const openMenus = ref<Record<string, boolean>>({});
const isMobile = ref(false);
const isMobileMenuOpen = ref(false);
const showLogoutModal = ref(false);

const getSidebarMenus = (userType: string): Record<string, SidebarMenuItem[]> => {
  const basePath = `/dashboard/${userType.toLowerCase()}`;
  return {
    Patient: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
      { name: 'Dental Chart', path: `/dentalChart`, icon: Activity },
      {
        name: 'Billing',
        path: `/dashboard/patient/billing`,
        icon: Receipt,
        children: [
          { name: 'Billings', path: `/dashboard/patient/billing`, icon: FileText },
          { name: 'Payments', path: `/dashboard/patient/billing/payment`, icon: CreditCard },
        ],
      },
    ],
    Owner: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Patients', path: `/dashboard/owner/records/PatientRecords`, icon: Users },
      { name: 'Appointments', path: `/dashboard/owner/appointments/AppointmentList`, icon: Calendar },
      {
        name: 'Billing',
        path: `/dashboard/owner/billing`,
        icon: Receipt,
        children: [
          { name: 'Billings', path: `/dashboard/owner/billing`, icon: FileText },
          { name: 'Payments', path: `/dashboard/owner/billing/payment`, icon: CreditCard },
        ],
      },
      {
        name: 'Clinic',
        path: `/dashboard/owner/clinic`,
        icon: Hospital,
        children: [
          { name: 'Staff Management', path: `/dashboard/owner/records/StaffManagement`, icon: Users },
          { name: 'Branches', path: `/dashboard/owner/clinic/BranchSettings`, icon: Building2 },
          { name: 'Services', path: `/dashboard/owner/clinic/ServicesList`, icon: List },
          { name: 'Payment Methods', path: `/dashboard/owner/clinic/PaymentMethod`, icon: Wallet },
          { name: 'Tooth Marks', path: `/dashboard/owner/clinic/ToothMarks`, icon: Activity },
        ],
      },
      { name: 'Data', path: `/dashboard/owner/data/Data`, icon: PieChart },
      { name: 'Reports', path: `/dashboard/owner/reports`, icon: FileText },
    ],
    Dentist: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Patients', path: `/dashboard/dentist/records/PatientRecords`, icon: Users },
      { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
    ],
    Staff: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Patients', path: `${basePath}/records/PatientRecords`, icon: Users },
      { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
      {
        name: 'Billing',
        path: `${basePath}/billing`,
        icon: Receipt,
        children: [
          { name: 'Billings', path: `${basePath}/billing`, icon: FileText },
          { name: 'Payments', path: `${basePath}/billing/payment`, icon: CreditCard },
        ],
      },
      {
        name: 'Clinic',
        path: `${basePath}/clinic`,
        icon: Hospital,
        children: [
          { name: 'Dentist Records', path: `${basePath}/records/DentistRecords`, icon: Stethoscope },
        ],
      },
    ],
  };
};

const menuItems = computed<SidebarMenuItem[]>(() => getSidebarMenus(userType.value)[userType.value] || []);

const toggleMenu = (menuName: string) => {
  openMenus.value[menuName] = !openMenus.value[menuName];
};

const handleLogout = () => {
  showLogoutModal.value = true;
};

const confirmLogout = () => {
  router.post(route('logout'));
};

const cancelLogout = () => {
  showLogoutModal.value = false;
};

const isActive = (path: string, item?: SidebarMenuItem) => {
  const currentPath = page.url.split('?')[0].replace(/\/$/, '');
  const normalizedPath = path.split('?')[0].replace(/\/$/, '');
  
  if (item?.name === 'Dashboard') return currentPath === normalizedPath;
  
  if (item?.children) {
    return item.children.some((sub) => {
      const subPath = sub.path.replace(/\/$/, '');
      return currentPath === subPath;
    });
  }
  
  return currentPath === normalizedPath;
};

watch(() => page.url, () => {
  menuItems.value.forEach((item) => {
    if (item.children && isActive(item.path, item)) {
      openMenus.value[item.name] = true;
    }
  });
});

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768;
  if (!isMobile.value) {
    isMobileMenuOpen.value = false;
  }
};

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
  menuItems.value.forEach((item) => {
    if (item.children && isActive(item.path, item)) {
      openMenus.value[item.name] = true;
    }
  });
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
});

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
};

watch(isCollapsed, (newValue) => {
  if (newValue) {
    openMenus.value = {};
  }
});
</script>

<template>
  <!-- Mobile Menu Toggle -->
  <div v-if="isMobile" class="md:hidden fixed top-4 left-4 z-50">
    <button
      @click="toggleMobileMenu"
      class="p-3 bg-[#1e4f4f] dark:bg-neutral-900 dark:border dark:border-neutral-700 text-white rounded-xl shadow-xl hover:bg-[#2a6565] dark:hover:bg-neutral-800 transition-colors"
    >
      <Menu :size="24" />
    </button>
  </div>

  <!-- Mobile Overlay -->
  <div
    v-if="isMobile && isMobileMenuOpen"
    @click="closeMobileMenu"
    class="fixed inset-0 bg-black bg-opacity-60 dark:bg-black/80 z-40 md:hidden backdrop-blur-sm"
  ></div>

  <!-- Sidebar -->
  <aside
    :class="[
      'h-screen bg-[#1e4f4f] dark:bg-neutral-900 dark:border-r dark:border-neutral-800 text-white dark:text-neutral-100 flex flex-col transition-all duration-300 ease-in-out',
      isMobile ? (isMobileMenuOpen ? 'w-80 fixed left-0 top-0 z-[1050] shadow-2xl' : 'w-0 -left-80') : (isCollapsed ? 'w-20' : 'w-80')
    ]"
  >
    <!-- Header -->
    <div class="py-6 px-6 border-b border-white/10 dark:border-neutral-800 dark:bg-neutral-900/50 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <img
          src="/images/DRP.png"
          alt="Logo"
          :class="['transition-all rounded-xl dark:rounded-lg shadow-md dark:shadow-lg dark:border dark:border-neutral-700', isCollapsed && !isMobile ? 'w-10 h-10' : 'w-14 h-14']"
        />
        <div v-if="!isCollapsed || isMobile" class="transition-all">
          <h2 class="text-xl font-bold text-white dark:text-neutral-100 tracking-tight">Dental Clinic</h2>
          <p class="text-xs text-white/60 dark:text-neutral-400 font-medium mt-0.5">{{ userType }} Portal</p>
        </div>
      </div>
      <button
        v-if="!isMobile"
        @click="isCollapsed = !isCollapsed"
        class="p-2 hover:bg-white/10 dark:hover:bg-neutral-800 rounded-lg transition-all duration-200 dark:text-neutral-400 dark:hover:text-neutral-100"
      >
        <ChevronLeft
          :size="20"
          :class="['transition-transform duration-300', isCollapsed ? 'rotate-180' : '']"
        />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-6 px-4 space-y-2 overflow-y-auto scrollbar-thin scrollbar-thumb-white/20 dark:scrollbar-thumb-neutral-700 scrollbar-track-transparent">
      <template v-for="item in menuItems" :key="item.name">
        <div>
          <template v-if="item.children">
            <!-- Parent Menu Item with Dropdown -->
            <button
              @click="toggleMenu(item.name)"
              :class="[
                'flex items-center w-full px-4 py-3.5 rounded-xl dark:rounded-lg text-[15px] font-semibold transition-all group relative',
                isActive(item.path, item) 
                  ? 'bg-white/15 dark:bg-emerald-600 text-white backdrop-blur-sm dark:shadow-lg dark:shadow-emerald-900/50' 
                  : 'text-white/80 dark:text-neutral-300 hover:bg-white/10 dark:hover:bg-neutral-800 hover:text-white dark:hover:text-neutral-100'
              ]"
            >
              <div :class="[
                'absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full transition-all dark:hidden',
                isActive(item.path, item) ? 'opacity-100' : 'opacity-0'
              ]"></div>
              <component 
                :is="item.icon" 
                :size="22" 
                :class="[
                  'flex-shrink-0 transition-all',
                  isCollapsed && !isMobile ? '' : 'mr-4',
                  isActive(item.path, item) ? 'text-white' : 'text-white/70 dark:text-neutral-400 group-hover:text-white dark:group-hover:text-neutral-200'
                ]"
              />
              <span
                v-if="!isCollapsed || isMobile"
                class="flex-1 text-left"
              >
                {{ item.name }}
              </span>
              <ChevronDown
                v-if="(!isCollapsed || isMobile)"
                :size="18"
                :class="[
                  'transition-transform duration-300 text-white/60 dark:text-neutral-400',
                  openMenus[item.name] ? 'rotate-180' : ''
                ]"
              />
            </button>

            <!-- Submenu -->
            <transition
              enter-active-class="transition-all duration-300 ease-out"
              leave-active-class="transition-all duration-200 ease-in"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-[600px]"
              leave-from-class="opacity-100 max-h-[600px]"
              leave-to-class="opacity-0 max-h-0"
            >
              <div
                v-if="(!isCollapsed || isMobile) && openMenus[item.name]"
                class="mt-2 mb-2 space-y-1.5 overflow-hidden"
              >
                <Link
                  v-for="sub in item.children"
                  :key="sub.path"
                  :href="sub.path"
                  @click="isMobile && closeMobileMenu()"
                  :class="[
                    'flex items-center px-4 py-2.5 ml-10 mr-2 rounded-lg text-[14px] transition-all relative group',
                    isActive(sub.path) 
                      ? 'bg-white/10 dark:bg-neutral-800 text-white dark:text-emerald-400 font-medium backdrop-blur-sm dark:border-l-2 dark:border-emerald-500' 
                      : 'text-white/70 dark:text-neutral-400 hover:bg-white/5 dark:hover:bg-neutral-800/50 hover:text-white dark:hover:text-neutral-200 hover:translate-x-1'
                  ]"
                >
                  <div :class="[
                    'absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 bg-white/50 rounded-full transition-all dark:hidden',
                    isActive(sub.path) ? 'opacity-100' : 'opacity-0 group-hover:opacity-50'
                  ]"></div>
                  <component 
                    :is="sub.icon" 
                    :size="18" 
                    :class="[
                      'mr-3 flex-shrink-0 transition-transform group-hover:scale-110',
                      isActive(sub.path) ? 'dark:text-emerald-400' : 'dark:text-neutral-500'
                    ]"
                  />
                  <span>{{ sub.name }}</span>
                </Link>
              </div>
            </transition>
          </template>
          <template v-else>
            <!-- Regular Menu Item -->
            <Link
              :href="item.path"
              @click="isMobile && closeMobileMenu()"
              :class="[
                'flex items-center w-full px-4 py-3.5 rounded-xl dark:rounded-lg text-[15px] font-semibold transition-all group relative',
                isActive(item.path, item) 
                  ? 'bg-white/15 dark:bg-emerald-600 text-white backdrop-blur-sm dark:shadow-lg dark:shadow-emerald-900/50' 
                  : 'text-white/80 dark:text-neutral-300 hover:bg-white/10 dark:hover:bg-neutral-800 hover:text-white dark:hover:text-neutral-100'
              ]"
            >
              <div :class="[
                'absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full transition-all dark:hidden',
                isActive(item.path, item) ? 'opacity-100' : 'opacity-0'
              ]"></div>
              <component 
                :is="item.icon" 
                :size="22" 
                :class="[
                  'flex-shrink-0 transition-all',
                  isCollapsed && !isMobile ? '' : 'mr-4',
                  isActive(item.path, item) ? 'text-white' : 'text-white/70 dark:text-neutral-400 group-hover:text-white dark:group-hover:text-neutral-200'
                ]"
              />
              <span v-if="!isCollapsed || isMobile">
                {{ item.name }}
              </span>
            </Link>
          </template>
        </div>
      </template>
    </nav>

    <!-- Footer -->
    <div class="px-4 py-6 border-t border-white/10 dark:border-neutral-800 dark:bg-neutral-900/30 space-y-2">
      <Link
        :href="`/settings/profile`"
        @click="isMobile && closeMobileMenu()"
        :class="[
          'flex items-center w-full px-4 py-3.5 rounded-xl dark:rounded-lg text-[15px] font-semibold transition-all group relative',
          isActive(`/settings/profile`) 
            ? 'bg-white/15 dark:bg-emerald-600 text-white backdrop-blur-sm dark:shadow-lg dark:shadow-emerald-900/50' 
            : 'text-white/80 dark:text-neutral-300 hover:bg-white/10 dark:hover:bg-neutral-800 hover:text-white dark:hover:text-neutral-100'
        ]"
      >
        <div :class="[
          'absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full transition-all dark:hidden',
          isActive(`/settings/profile`) ? 'opacity-100' : 'opacity-0'
        ]"></div>
        <Settings 
          :size="22" 
          :class="[
            'flex-shrink-0 transition-all',
            isCollapsed && !isMobile ? '' : 'mr-4',
            isActive(`/settings/profile`) ? 'text-white' : 'text-white/70 dark:text-neutral-400 group-hover:text-white dark:group-hover:text-neutral-200'
          ]"
        />
        <span v-if="!isCollapsed || isMobile">Settings</span>
      </Link>
      <button
        @click="handleLogout"
        class="flex items-center w-full px-4 py-3.5 rounded-xl dark:rounded-lg text-[15px] font-semibold transition-all group text-white/80 dark:text-neutral-300 hover:bg-red-500/20 dark:hover:bg-red-900/30 hover:text-red-200 dark:hover:text-red-400 dark:border dark:border-transparent dark:hover:border-red-800/50"
      >
        <LogOut 
          :size="22" 
          :class="[
            'flex-shrink-0 text-white/70 dark:text-neutral-400 group-hover:text-red-200 dark:group-hover:text-red-400 transition-all',
            isCollapsed && !isMobile ? '' : 'mr-4'
          ]"
        />
        <span v-if="!isCollapsed || isMobile">Logout</span>
      </button>
    </div>
  </aside>

  <!-- Logout Confirmation Modal -->
  <div v-if="showLogoutModal" class="fixed inset-0 z-[2000] flex items-center justify-center">
    <div class="absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-sm" @click="cancelLogout"></div>
    <div class="relative bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 border border-neutral-200 dark:border-neutral-700">
      <div class="flex flex-col items-center text-center">
        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
          <LogOut :size="32" class="text-red-600 dark:text-red-400" />
        </div>
        <h3 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100 mb-2">Logout Confirmation</h3>
        <p class="text-neutral-600 dark:text-neutral-400 mb-6">Are you sure you want to logout?</p>
        <div class="flex gap-3 w-full">
          <button
            @click="cancelLogout"
            class="flex-1 px-4 py-3 bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-lg font-semibold hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="confirmLogout"
            class="flex-1 px-4 py-3 bg-red-600 dark:bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 dark:hover:bg-red-700 transition-colors shadow-lg shadow-red-600/30"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Scrollbar styling */
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgb(55, 65, 81);
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

.dark .scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: rgb(75, 85, 99);
}

/* Add subtle backdrop blur for glass effect on active items */
@supports (backdrop-filter: blur(10px)) {
  .backdrop-blur-sm {
    backdrop-filter: blur(10px);
  }
}
</style>