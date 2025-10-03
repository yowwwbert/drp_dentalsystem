<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import * as Icons from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import type { Auth } from '@/types';

const { LayoutDashboard, Calendar, Clipboard, FileText, BarChart, Hospital, UserCircle, UserCog, MapPin, ListOrdered, ChartBar, Settings, LogOut, Menu, ChevronRight } = Icons;

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

const getSidebarMenus = (userType: string): Record<string, SidebarMenuItem[]> => {
  const basePath = `/dashboard/${userType.toLowerCase()}`;
  return {
    Patient: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
      { name: 'Dental Chart', path: `${basePath}/dentalChart`, icon: Clipboard },
      {
        name: 'Billing',
        path: `/dashboard/patient/billing`,
        icon: FileText,
        children: [
          { name: 'Billings', path: `/dashboard/patient/billing`, icon: FileText },
          { name: 'Payments', path: `/dashboard/patient/billing/payment`, icon: BarChart },
        ],
      },
    ],
    Owner: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Patients', path: `/dashboard/owner/records/PatientRecords`, icon: Clipboard },
      { name: 'Appointments', path: `/dashboard/owner/appointments/AppointmentList`, icon: Calendar },
      {
        name: 'Billing',
        path: `/dashboard/owner/billing`,
        icon: FileText,
        children: [
          { name: 'Billings', path: `/dashboard/owner/billing`, icon: FileText },
          { name: 'Payments', path: `/dashboard/owner/billing/payment`, icon: BarChart },
        ],
      },
      {
        name: 'Clinic',
        path: `/dashboard/owner/clinic`,
        icon: Hospital,
        children: [
          { name: 'Dentists', path: `/dashboard/owner/records/DentistRecords`, icon: UserCircle },
          { name: 'Staff', path: `/dashboard/owner/records/StaffRecords`, icon: UserCog },
          { name: 'Branches', path: `/dashboard/owner/clinic/BranchSettings`, icon: MapPin },
          { name: 'Services', path: `/dashboard/owner/clinic/ServicesList`, icon: ListOrdered },
          { name: 'Payment Methods', path: `/dashboard/owner/clinic/PaymentMethod`, icon: ChartBar },
          { name: 'Tooth Marks', path: `/dashboard/owner/clinic/ToothMarks`, icon: Clipboard },
        ],
      },
      { name: 'Data', path: `/dashboard/owner/data/Data`, icon: ChartBar },
      { name: 'Reports', path: `/dashboard/owner/reports`, icon: FileText },
    ],
    Dentist: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
      { name: 'Dental Chart', path: `${basePath}/records/dentalChart`, icon: Clipboard },
    ],
    Receptionist: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
      { name: 'Patients', path: `${basePath}/records/PatientRecords`, icon: Clipboard },
      { name: 'Billing', path: `${basePath}/billing/Billing`, icon: FileText },
    ],
  };
};

const menuItems = computed<SidebarMenuItem[]>(() => getSidebarMenus(userType.value)[userType.value] || []);

const toggleMenu = (menuName: string) => {
  openMenus.value[menuName] = !openMenus.value[menuName];
};

const handleLogout = () => {
  router.post(route('logout'));
};

const isActive = (path: string, item?: SidebarMenuItem) => {
  const currentPath = page.url.split('?')[0].replace(/\/$/, '');
  const normalizedPath = path.split('?')[0].replace(/\/$/, '');
  if (item?.name === 'Dashboard') return currentPath === normalizedPath;
  if (item?.children) {
    return (
      currentPath === normalizedPath ||
      item.children.some((sub) => currentPath.includes(sub.path.replace(/\/$/, '')))
    );
  }
  return currentPath.includes(normalizedPath);
};

// Keep dropdown open if a subitem is active
watch(page.url, () => {
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
  const savedState = localStorage.getItem('sidebar-collapsed');
  if (savedState !== null) {
    isCollapsed.value = JSON.parse(savedState);
  }
  checkMobile();
  window.addEventListener('resize', checkMobile);
  // Initialize open menus for active subitems
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
  localStorage.setItem('sidebar-collapsed', JSON.stringify(newValue));
  if (newValue) {
    openMenus.value = {};
    // Re-open menus for active subitems when collapsing
    menuItems.value.forEach((item) => {
      if (item.children && isActive(item.path, item)) {
        openMenus.value[item.name] = true;
      }
    });
  }
});
</script>

<template>
  <!-- Mobile Menu Toggle -->
  <div v-if="isMobile" class="md:hidden fixed top-4 left-4 z-50">
    <button
      @click="toggleMobileMenu"
      class="p-3 bg-darkGreen-900 text-white rounded-md shadow-lg"
    >
      <Menu :size="24" />
    </button>
  </div>

  <!-- Mobile Overlay -->
  <div
    v-if="isMobile && isMobileMenuOpen"
    @click="closeMobileMenu"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
  ></div>

  <!-- Sidebar -->
  <aside
    :class="[
      'h-screen bg-darkGreen-900 text-white flex flex-col transition-all duration-300 ease-in-out shadow-lg',
      isMobile ? (isMobileMenuOpen ? 'w-72 fixed left-0 top-0 z-[1050]' : 'w-0 -left-72') : (isCollapsed ? 'w-20' : 'w-72')
    ]"
  >
    <!-- Header -->
    <div class="py-6 px-6 border-b border-darkGreen-700 flex items-center justify-between h-20">
      <img
        src="/images/DRP.png"
        alt="Logo"
        :class="['transition-all', isCollapsed && !isMobile ? 'w-12 h-12' : 'w-16 h-16']"
      />
      <button
        v-if="!isMobile"
        @click="isCollapsed = !isCollapsed"
        class="p-2 bg-darkGreen-800 rounded-full hover:bg-darkGreen-700 relative -mr-2"
      >
        <ChevronRight
          :size="24"
          :class="['transition-transform', isCollapsed ? 'rotate-180' : '']"
        />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-6 px-4 space-y-4 overflow-y-auto">
      <template v-for="item in menuItems" :key="item.name">
        <div class="relative">
          <template v-if="item.children">
            <!-- Parent Menu Item -->
            <button
              @click="toggleMenu(item.name)"
              :class="[
                'flex items-center w-full px-4 py-3 rounded-xl text-base font-semibold transition-colors',
                isActive(item.path, item) ? 'bg-darkGreen-600 text-white shadow-md' : 'hover:bg-darkGreen-700'
              ]"
            >
              <component :is="item.icon" :size="24" class="mr-4" />
              <span
                :class="[
                  'flex-1 text-left',
                  isCollapsed && !isMobile ? 'hidden' : 'block'
                ]"
              >
                {{ item.name }}
              </span>
              <ChevronRight
                v-if="!isCollapsed || isMobile"
                :size="18"
                :class="['transition-transform', openMenus[item.name] ? 'rotate-90' : '']"
              />
            </button>

            <!-- Submenu -->
            <transition
              enter-active-class="transition-all duration-200 ease-out"
              leave-active-class="transition-all duration-200 ease-in"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-[500px]"
              leave-from-class="opacity-100 max-h-[500px]"
              leave-to-class="opacity-0 max-h-0"
            >
              <div
                v-if="(!isCollapsed || isMobile) && openMenus[item.name]"
                class="ml-6 mt-3 space-y-2 border-l-2 border-darkGreen-700 pl-4"
              >
                <Link
                  v-for="sub in item.children"
                  :key="sub.path"
                  :href="sub.path"
                  @click="isMobile && closeMobileMenu()"
                  :class="[
                    'flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-colors',
                    isActive(sub.path) ? 'bg-darkGreen-500 text-white shadow-md' : 'hover:bg-darkGreen-700'
                  ]"
                >
                  <component :is="sub.icon" :size="20" class="mr-3" />
                  <span
                    :class="[isCollapsed && !isMobile ? 'hidden' : 'block']"
                  >
                    {{ sub.name }}
                  </span>
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
                'flex items-center w-full px-4 py-3 rounded-xl text-base font-semibold transition-colors',
                isActive(item.path, item) ? 'bg-darkGreen-600 text-white shadow-md' : 'hover:bg-darkGreen-700'
              ]"
            >
              <component :is="item.icon" :size="24" class="mr-4" />
              <span
                :class="[isCollapsed && !isMobile ? 'hidden' : 'block']"
              >
                {{ item.name }}
              </span>
            </Link>
          </template>
        </div>
      </template>
    </nav>

    <!-- Footer -->
    <div class="px-4 py-6 border-t border-darkGreen-700 space-y-4">
      <Link
        :href="`/settings/profile`"
        @click="isMobile && closeMobileMenu()"
        :class="[
          'flex items-center w-full px-4 py-3 rounded-xl text-base font-semibold transition-colors',
          isActive(`/settings/profile`) ? 'bg-darkGreen-600 text-white shadow-md' : 'hover:bg-darkGreen-700'
        ]"
      >
        <Settings :size="24" class="mr-4" />
        <span :class="[isCollapsed && !isMobile ? 'hidden' : 'block']">
          Settings
        </span>
      </Link>
      <button
        @click="handleLogout"
        class="flex items-center w-full px-4 py-3 rounded-xl text-base font-semibold transition-colors hover:bg-darkGreen-700"
      >
        <LogOut :size="24" class="mr-4" />
        <span :class="[isCollapsed && !isMobile ? 'hidden' : 'block']">
          Logout
        </span>
      </button>
    </div>
  </aside>
</template>

<style scoped>
.bg-darkGreen-900 {
  background-color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #1a4545;
}

.bg-darkGreen-700 {
  background-color: #2d6a6a;
}

.bg-darkGreen-600 {
  background-color: #3a8585;
}

.bg-darkGreen-500 {
  background-color: #4a9f9f;
}

.border-darkGreen-700 {
  border-color: #2d6a6a;
}


.shadow-md {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
}
</style>