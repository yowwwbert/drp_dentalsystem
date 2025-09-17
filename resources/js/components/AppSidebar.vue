<script setup lang="ts">
  import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
  import { useSidebar } from '@/components/ui/sidebar';
  import { usePage, Link, router } from '@inertiajs/vue3';
  import { Move, LayoutDashboard, Calendar, FileText, Users, Clipboard, BarChart, LogOut, Hospital, ChartBar, UserCog, MapPin, ListOrdered, Settings, UserCircle } from 'lucide-vue-next';
  import AppLogo from './AppLogo.vue';
  import type { Auth } from '@/types';


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
  const hoveredMenu = ref<string | null>(null);
  const menuRefs = ref<Record<string, HTMLElement | null>>({});
  const sidebarRef = ref<HTMLElement | null>(null);
  const hoverTimeout = ref<any>(null);
  const isMobile = ref(false);
  const isMobileMenuOpen = ref(false);

  // Check if mobile on mount and resize
  const checkMobile = () => {
    isMobile.value = window.innerWidth < 768;
    if (!isMobile.value) {
      isMobileMenuOpen.value = false;
    }
  };

  const getSidebarMenus = (userType: string): Record<string, SidebarMenuItem[]> => {
    const basePath = `/dashboard/${userType.toLowerCase()}`;
    return {
      Patient: [
        { name: 'Dashboard', path: `/dashboard`, icon: LayoutDashboard },
        { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
      ],
      Owner: [
        { name: 'Dashboard', path: `/dashboard`, icon: LayoutDashboard },
        { name: 'Patient', path: `/dashboard/owner/records/PatientRecords`, icon: Clipboard },
        { name: 'Appointments', path: `/dashboard/owner/appointments/AppointmentList`, icon: Calendar },
        { name: 'Billing', path: `/dashboard/owner/billing/Billing`, icon: FileText },
        {
          name: 'Clinic',
          path: `/dashboard/owner/clinic`,
          icon: Hospital,
          children: [
            { name: 'Dentists', path: `/dashboard/owner/records/DentistRecords`, icon: UserCircle },
            { name: 'Staff', path: `/dashboard/owner/records/StaffRecords`, icon: UserCog },
            { name: 'Branches', path: `/dashboard/owner/clinic/BranchSettings`, icon: MapPin },
            { name: 'Services', path: `/dashboard/owner/clinic/ServicesList`, icon: ListOrdered },
          ],
        },
        { name: 'Data', path: `/dashboard/owner/data/Data`, icon: ChartBar },
        { name: 'Reports', path: `/dashboard/owner/reports/Reports`, icon: FileText },
      ],
      Dentist: [
        { name: 'Dashboard', path: `/dashboard`, icon: LayoutDashboard },
        { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
        { name: 'Dental Chart', path: `${basePath}/records/dentalChart`, icon: Users },
      ],
      Receptionist: [
        { name: 'Dashboard', path: `/dashboard`, icon: LayoutDashboard },
        { name: 'Appointments', path: `${basePath}/appointments/AppointmentList`, icon: Calendar },
        { name: 'Patient', path: `${basePath}/records/PatientRecords`, icon: Clipboard },
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

  const handleMouseEnter = (menuName: string, event: MouseEvent) => {
    if (hoverTimeout.value) clearTimeout(hoverTimeout.value);
    if (isCollapsed.value) {
      hoveredMenu.value = menuName;
    }
  };

  const handleMouseLeave = () => {
    if (isCollapsed.value) {
      hoverTimeout.value = setTimeout(() => {
        hoveredMenu.value = null;
      }, 250);
    }
  };

  const clearHoverTimeout = () => {
    if (hoverTimeout.value) {
      clearTimeout(hoverTimeout.value);
    }
  };

  const isActive = (path: string, item?: any) => {
    const currentPath = page.url.split('?')[0].replace(/\/$/, '');
    const normalizedPath = path.split('?')[0].replace(/\/$/, '');
    if (item?.name === 'Dashboard') return currentPath === normalizedPath;
    if (item?.children) {
      return (
        currentPath === normalizedPath ||
        item.children.some((sub: any) => currentPath.includes(sub.path.replace(/\/$/, '')))
      );
    }
    return currentPath.includes(normalizedPath);
  };

  onMounted(() => {
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('sidebar-collapsed');
    if (savedState !== null) {
      isCollapsed.value = JSON.parse(savedState);
    }
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
  });

  // Cleanup event listener
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
    // Save sidebar state to localStorage
    localStorage.setItem('sidebar-collapsed', JSON.stringify(newValue));
    // Clear open menus when collapsed
    if (newValue) {
      openMenus.value = {};
    }
  });
</script>

<template>
  <!-- Mobile Menu Toggle -->
  <div v-if="isMobile" class="md:hidden fixed top-4 left-4 z-50">
    <button @click="toggleMobileMenu" class="p-2 bg-darkGreen-900 text-white rounded-md shadow-lg">
      <Move :size="20" />
    </button>
  </div>

  <!-- Mobile Overlay -->
  <div v-if="isMobile && isMobileMenuOpen" @click="closeMobileMenu" class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"></div>

  <!-- Sidebar -->
  <div ref="sidebarRef" :class="[
    'h-screen bg-darkGreen-900 text-white flex flex-col relative transition-all duration-300 ease-in-out',
    isMobile ? (isMobileMenuOpen ? 'w-64 fixed left-0 top-0 z-[1050]' : 'w-0 -left-64') : (isCollapsed ? 'w-16 z-[1050]' : 'w-64 z-[1050]')
  ]" style="overflow: visible;">
    <!-- Logo + Toggle -->
    <div class="p-4 border-b border-darkGreen-800 flex flex-col items-center h-32">
      <img src="/images/DRP.png" alt="Logo" :class="['transition-all', isCollapsed && !isMobile ? 'w-10 h-10' : 'w-12 h-12']" />
      <button v-if="!isMobile" @click="isCollapsed = !isCollapsed" class="mt-3 p-1 bg-hoverGreen-700 rounded-full hover:bg-darkGreen-800">
        <Move :size="20" :class="isCollapsed ? 'rotate-45' : ''" />
      </button>
    </div>
    <!-- Navigation -->
    <div class="flex-1 p-2 mt-4 space-y-2">
      <template v-for="item in menuItems" :key="item.name">
        <div class="relative group" @mouseenter="!isMobile && handleMouseEnter(item.name, $event)" @mouseleave="!isMobile && handleMouseLeave">
          <template v-if="item.children">
            <!-- Parent menu item with dropdown -->
            <button @click="toggleMenu(item.name)" :class="['flex items-center w-full px-3 py-2 rounded-md text-sm font-medium transition-colors', isActive(item.path, item) ? 'bg-hoverGreen-700' : 'hover:bg-hoverGreen-700']">
              <span class="mr-3 flex justify-center"><component :is="item.icon" :size="22" /></span>
              <div :class="['flex-1 transition-all duration-300', (isCollapsed && !isMobile) ? 'opacity-0 w-0' : 'opacity-100 w-auto']">
                <div class="flex justify-between items-center">
                  <span>{{ item.name }}</span>
                  <span v-if="!isCollapsed || isMobile" :class="['transition-transform duration-200', openMenus[item.name] ? 'rotate-180' : '']">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="6,9 12,15 18,9"></polyline>
                    </svg>
                  </span>
                </div>
              </div>
            </button>
            
            <!-- Dropdown sub-items -->
            <transition name="dropdown" appear>
              <div v-if="(!isCollapsed || isMobile) && openMenus[item.name]" class="space-y-1">
                <template v-for="sub in item.children" :key="sub.path">
                  <Link :href="sub.path" @click="isMobile && closeMobileMenu()" :class="['flex items-center w-full px-3 py-2 rounded-md text-sm font-medium transition-colors', isActive(sub.path) ? 'bg-hoverGreen-700' : 'hover:bg-hoverGreen-700']">
                    <span class="mr-3 flex justify-center"><component :is="sub.icon" :size="18" /></span>
                    <span :class="[(isCollapsed && !isMobile) ? 'opacity-0 w-0' : 'opacity-100 w-auto', 'transition-all']">{{ sub.name }}</span>
                  </Link>
                </template>
              </div>
            </transition>
            
            <!-- Hover Popup for Submenu (Desktop only) -->
            <div v-if="!isMobile && isCollapsed && hoveredMenu === item.name" class="absolute left-full top-0 z-[1000] w-48 bg-darkGreen-900 border border-darkGreen-700 rounded-r-md shadow-lg p-2" @mouseenter="clearHoverTimeout" @mouseleave="handleMouseLeave">
              <div class="text-white font-semibold px-2 pb-1">{{ item.name }}</div>
              <div class="mt-1 space-y-1">
                <template v-for="sub in item.children" :key="sub.path">
                  <Link :href="sub.path" :class="['flex items-center gap-2 px-3 py-1.5 rounded-md text-sm', isActive(sub.path) ? 'bg-hoverGreen-700' : 'hover:bg-hoverGreen-700']">
                    <span><component :is="sub.icon" :size="20" /></span>
                    <span>{{ sub.name }}</span>
                  </Link>
                </template>
              </div>
            </div>
          </template>
          <template v-else>
            <!-- Regular menu item -->
            <Link :href="item.path" @click="isMobile && closeMobileMenu()" :class="['flex items-center w-full px-3 py-2 rounded-md text-sm font-medium transition-colors', isActive(item.path, item) ? 'bg-hoverGreen-700' : 'hover:bg-hoverGreen-700']">
              <span class="mr-3 flex justify-center"><component :is="item.icon" :size="22" /></span>
              <span :class="[(isCollapsed && !isMobile) ? 'opacity-0 w-0' : 'opacity-100 w-auto', 'transition-all']">{{ item.name }}</span>
            </Link>
            <!-- Hover Popup for Regular Menu Item (Desktop only) -->
            <Link v-if="!isMobile && isCollapsed && hoveredMenu === item.name" :href="item.path" class="absolute left-full top-0 z-[1000] w-48 bg-darkGreen-900 border border-darkGreen-700 rounded-r-md shadow-lg p-2 text-sm text-white">
              {{ item.name }}
            </Link>
          </template>
        </div>
      </template>
    </div>
    <!-- Footer -->
    <div class="p-2 border-t border-darkGreen-800 mt-auto space-y-2">
      <div class="relative group" @mouseenter="!isMobile && handleMouseEnter('Settings', $event)" @mouseleave="!isMobile && handleMouseLeave">
        <div ref="el => menuRefs['Settings'] = el" class="flex items-center relative">
          <Link :href="`/settings/profile`" @click="isMobile && closeMobileMenu()" :class="['flex items-center w-full px-3 py-2 rounded-md text-sm font-medium transition-colors', isActive(`/settings/profile`) ? 'bg-hoverGreen-700' : 'hover:bg-hoverGreen-700']">
            <span class="mr-3 flex justify-center"><Settings :size="22" /></span>
            <span :class="[(isCollapsed && !isMobile) ? 'opacity-0 w-0' : 'opacity-100 w-auto', 'transition-all']">Settings</span>
          </Link>
          <!-- Hover Popup for Settings (Desktop only) -->
          <Link v-if="!isMobile && isCollapsed && hoveredMenu === 'Settings'" :href="`/settings/profile`" class="absolute left-full top-0 z-[1000] w-48 bg-darkGreen-900 border border-darkGreen-700 rounded-r-md shadow-lg p-2 text-sm text-white">
            Settings
          </Link>
        </div>
      </div>
      <div class="relative group" @mouseenter="!isMobile && handleMouseEnter('Logout', $event)" @mouseleave="!isMobile && handleMouseLeave">
        <div ref="el => menuRefs['Logout'] = el" class="flex items-center relative">
          <button @click="handleLogout" class="flex items-center w-full px-3 py-2 rounded-md text-sm font-medium hover:bg-hoverGreen-700">
            <span class="mr-3 flex justify-center"><LogOut :size="22" /></span>
            <span :class="[(isCollapsed && !isMobile) ? 'opacity-0 w-0' : 'opacity-100 w-auto', 'transition-all']">Logout</span>
          </button>
          <!-- Hover Popup for Logout (Desktop only) -->
          <button v-if="!isMobile && isCollapsed && hoveredMenu === 'Logout'" @click="handleLogout" class="absolute left-full top-0 z-[1000] w-48 bg-darkGreen-900 border border-darkGreen-700 rounded-r-md shadow-lg p-2 text-sm text-white text-left">
            Logout
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bg-darkGreen-900 {
  background-color: #1e4f4f;
}

.bg-darkGreen-800 {
  background-color: #1a4545;
}

.bg-hoverGreen-700 {
  background-color: #2d6a6a;
}

.border-darkGreen-700 {
  border-color: #2d6a6a;
}

.border-darkGreen-600 {
  border-color: #3a8585;
}

/* Dropdown transition styles */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
  max-height: 0;
}

.dropdown-enter-to,
.dropdown-leave-from {
  opacity: 1;
  transform: translateY(0);
  max-height: 200px;
}
</style>