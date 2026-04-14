import './assets/main.css'
import './assets/datepicker.css'
import './assets/stat-card.css'

import {
  createApp
} from 'vue'
import {
  createPinia
} from 'pinia'
import App from './App.vue'
import router from './router'

import VueSweetalert2 from 'vue-sweetalert2'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'

// Font Awesome
import {
  library
} from '@fortawesome/fontawesome-svg-core'
import {
  FontAwesomeIcon
} from '@fortawesome/vue-fontawesome'
import {
  faFilter,
  faCalendar,
  faFileAlt,
  faFileExport,
  faSave,
  faBolt,
  faDatabase,
  faChartLine,
  faDownload,
  faCloudDownloadAlt,
  faEye,
  faBars,
  faChartBar,
  faCog,
  faChevronDown,
  faUsers,
  faHome,
  faBuilding,
  faArchive,
  faDollarSign,
  faTint,
  faPlus,
  faSearch,
  faBell,
  faQuestionCircle,
  faUser,
  faSignOutAlt,
  faTimes,
  faLock,
  faEyeSlash,
  faSpinner,
  faArrowRight,
  faArrowDown,
  faArrowUp,
  faInfoCircle,
  faWater,
  faChartColumn,
  faBuildingUser,
  faBalanceScale,
  faFileInvoice,
  faFilePowerpoint,
  faAssistiveListeningSystems,
  faCreditCard,
  faUserPlus,
  faShieldHalved,
  faCheckCircle,
  faTag,
  faExclamationTriangle,
  faHourglassHalf,
  faChevronUp,
  faIdCard,
  faMapMarkerAlt,
  faQrcode,
  faFrown,
  faCheck,
  faEdit,
  faTrash,
  faPrint,
} from '@fortawesome/free-solid-svg-icons'

// Tambahkan icons ke library
library.add(
  faFilter,
  faCalendar,
  faFileAlt,
  faFileExport,
  faSave,
  faBolt,
  faDatabase,
  faChartLine,
  faDownload,
  faCloudDownloadAlt,
  faEye,
  faBars,
  faChartBar,
  faCog,
  faChevronDown,
  faUsers,
  faHome,
  faBuilding,
  faArchive,
  faDollarSign,
  faTint,
  faPlus,
  faSearch,
  faBell,
  faQuestionCircle,
  faUser,
  faSignOutAlt,
  faTimes,
  faLock,
  faEyeSlash,
  faSpinner,
  faArrowRight,
  faArrowDown,
  faArrowUp,
  faInfoCircle,
  faWater,
  faChartColumn,
  faBuildingUser,
  faBalanceScale,
  faFileInvoice,
  faFilePowerpoint,
  faAssistiveListeningSystems,
  faCreditCard,
  faUserPlus,
  faShieldHalved,
  faCheckCircle,
  faTag,
  faExclamationTriangle,
  faHourglassHalf,
  faChevronUp,
  faIdCard,
  faMapMarkerAlt,
  faQrcode,
  faFrown,
  faCheck,
  faEdit,
  faTrash,
  faPrint,
)

const options = {
  confirmButtonColor: '#ff0000',
  cancelButtonColor: '#ff7674',
}

export const MySwal = Swal.mixin(options)

// Create app instance
const app = createApp(App)

// Create Pinia instance
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(VueSweetalert2)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
  },
})

// Register PrimeVue components
app.component('PrimeButton', Button)
app.component('DatePicker', DatePicker)
app.component('InputNumber', InputNumber)

// Register Font Awesome component
app.component('font-awesome-icon', FontAwesomeIcon)

app.mount('#app')
