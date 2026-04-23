import './assets/css/main.css'
import './assets/css/datepicker.css'
import './assets/css/stat-card.css'
import './assets/css/ui-base.css'
import './assets/css/ui-modals.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

import VueSweetalert2 from 'vue-sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Toast from 'primevue/toast'
import ToastService from 'primevue/toastservice'
import ProgressBar from 'primevue/progressbar'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
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
  faChevronRight,
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
  faUnlock,
  faEyeSlash,
  faSpinner,
  faArrowLeft,
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
  faCamera,
  faUserEdit,
  faKey,
  faChevronLeft,
  faImage,
  faFileInvoiceDollar,
  faFaucet,
  faWrench,
  faHandHoldingWater,
  faCalendarDay,
  faRedoAlt,
  faRobot,
  faHistory,
  faFileImage,
  faWeightHanging,
  faDroplet,
  faShieldAlt,
  faCloud,
  faServer,
  faGlobe,
  faEnvelope,
  faPhone,
  faBan,
  faTimesCircle,
  faSyncAlt,
  faCalendarAlt,
  faHashtag,
  faSitemap,
  faCalendarCheck,
  faClipboardList,
  faClock,
  faCheckDouble,
  faMapMarkedAlt,
  faExpand,
  faUserClock,
  faMapPin,
  faTools,
  faPlusCircle,
  faProjectDiagram,
  faBoxes,
  faHardHat,
  faWallet,
  faReceipt,
  faHeadset,
  faBullhorn,
} from '@fortawesome/free-solid-svg-icons'

import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'

library.add(
  faFilter,
  faCalendarAlt,
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
  faChevronRight,
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
  faUnlock,
  faEyeSlash,
  faSpinner,
  faArrowLeft,
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
  faCamera,
  faUserEdit,
  faKey,
  faChevronLeft,
  faImage,
  faWhatsapp,
  faFileInvoiceDollar,
  faFaucet,
  faWrench,
  faHandHoldingWater,
  faCalendarDay,
  faRedoAlt,
  faRobot,
  faHistory,
  faFileImage,
  faWeightHanging,
  faDroplet,
  faShieldAlt,
  faCloud,
  faServer,
  faGlobe,
  faEnvelope,
  faPhone,
  faBan,
  faTimesCircle,
  faSyncAlt,
  faHashtag,
  faSitemap,
  faCalendarCheck,
  faClipboardList,
  faClock,
  faCheckDouble,
  faMapMarkedAlt,
  faExpand,
  faUserClock,
  faMapPin,
  faTools,
  faPlusCircle,
  faProjectDiagram,
  faBoxes,
  faHardHat,
  faWallet,
  faReceipt,
  faHeadset,
  faBullhorn,
)

export { MySwal } from './utils/swal'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(VueSweetalert2)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
  },
  zIndex: {
    modal: 1100,
    overlay: 10000,
    menu: 1000,
    tooltip: 1100,
  },
})
app.use(ToastService)

app.component('PrimeButton', Button)
app.component('DatePicker', DatePicker)
app.component('InputNumber', InputNumber)
app.component('PrimeSelect', Select)
app.component('PrimeToast', Toast)
app.component('ProgressBar', ProgressBar)
app.component('font-awesome-icon', FontAwesomeIcon)

app.mount('#app')
