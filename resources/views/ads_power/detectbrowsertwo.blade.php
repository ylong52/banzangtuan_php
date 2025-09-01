<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DetectbrowserTwo</title>
    <script src="/static/vue3/vue.global.js"></script>
    <!-- 引入 axios 用于发送HTTP请求 -->
    <script src="/static/vue3/axios.min.js"></script>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LcXyRorAAAAAOjOywIdavpTbKL1DscyyvEiPJ1F"></script>
    <script src="https://geoip-js.com/js/apis/geoip2/v2.1/geoip2.js" type="text/javascript"></script>
    <!-- Your code -->
</head>
<body>
<div id="app">

    <div  class="card"  >
        <p style="text-align: right;">IP检查链接2</p>
        <div class="card-content" :class="scoreClass "  v-if="cityInfo.ip_address">
            <p>@{{ cityInfo.ip_address }}</p>
            <p>
             @{{ cityInfo.country }} /@{{ cityInfo.subdivision }} / @{{ cityInfo.city }} / @{{ cityInfo.zipcode }}
            </p>
        </div>
        <pre style="display:none;">@{{ JSON.stringify(response, null, 2) }}</pre>

    </div>

    <div class="card environ">

        <div class="card-content" v-if="vbRiskAnalysis">
            <ul style=" text-align: left;">
                <li>Enviromental Score: @{{ threat_potential_score }}</li>
                <li>Browser: @{{ browserInfo.browser }} / @{{ browserInfo.browser_version }} </li>
                <li>System Model: @{{ browserInfo.os }}/ @{{ browserInfo.os_version }}</li>
            </ul>
        </div>
    </div>
</div>
<body>
<script>

var siteKey = '6LcXyRorAAAAAOjOywIdavpTbKL1DscyyvEiPJ1F'
var apiKey = 'AIzaSyAorM44ufR0BhpuRCEiB-xKOPBmNTFfns0'
var prouect_id = 54360
var project = "first-tine-457104-m6"
var user_action = 'login'


const { createApp, ref, onMounted, computed } = Vue

const app = createApp({
    setup() {
        const threat_potential_score = ref(null)
        const vbRiskAnalysis = ref(false)
        const riskAnalysis = ref({
            score: 0
        })
        const response = ref({
            Respondent: {
                country_code: '',
                threat_potential_score: 0
            }
        })
        const browserInfo = ref({
            browser: '',
            browser_version: '',
            os: '',
            os_version: ''
        })
        const ipAddress = ref('')
        const cityInfo = ref({})


        const scoreClass = computed(() => {
            // reCAPTCHA 的评分系统是从先前版本的 reCAPTCHA 扩展而来，能够更精细地进行响应。reCAPTCHA 的得分在 0.0 到 1.0 之间，有 11 个级别。1.0 分表示互动风险低，很可能是合法的，0.0 表示互动风险高，可能具有欺诈性。
            if (threat_potential_score.value >= 0 && threat_potential_score.value < 0.7) {
                return 'red-score'
            } else {

                return 'blue-score'
            }
        })

        const detectBrowserAndOS = () => {
            const userAgent = navigator.userAgent
            const browserRegex = /(Chrome|Firefox|Safari|Edge|IE)\/?\s*(\d+(\.\d+)*)/
            const match = userAgent.match(browserRegex)
            if (match) {
                browserInfo.value.browser = match[1]
                browserInfo.value.browser_version = match[2]
            }

            if (userAgent.includes('Windows')) {
                const windowsVersion = userAgent.match(/Windows NT (\d+\.\d+)/)
                const versionMap = {
                    '10.0': '10',
                    '6.3': '8.1',
                    '6.2': '8',
                    '6.1': '7',
                    '6.0': 'Vista'
                }

                browserInfo.value.os = 'Windows'
                browserInfo.value.os_version = windowsVersion ? versionMap[windowsVersion[1]] || windowsVersion[1] : ''
            } else if (userAgent.includes('Mac')) {
                browserInfo.value.os = 'MacOS'
                browserInfo.value.os_version = userAgent.match(/Mac OS X\s*([\w\d_.]+)?/i)?.[1]?.replace(/_/g, '.') || ''
            } else if (userAgent.includes('Linux')) {
                browserInfo.value.os = 'Linux'
                browserInfo.value.os_version = userAgent.match(/Linux\s*([\w\d.]+)?/i)?.[1] || ''
            }
        }

        const  callRecaptchaenterprise = (request) => {
            const url = `https://recaptchaenterprise.googleapis.com/v1/projects/${project}/assessments?key=${apiKey}`
            axios.post(url, request).then(function (response) {
                console.log("recaptchaenterprise.googleapis.com response.data >>>>", response.data)
                vbRiskAnalysis.value = true
                if (response.data.tokenProperties.valid) {
                    riskAnalysis.value = response.data.riskAnalysis
                    threat_potential_score.value = riskAnalysis.value.score
                    // 调用store
                    store();
                    console.log("Updated risk analysis:", riskAnalysis.value)
                } else {
                    console.log('Invalid token')
                }
            })
        }

        const store = () => {
            if (!cityInfo.value.ip_address || cityInfo.value.ip_address == '' || cityInfo.value.ip_address == undefined) {
                return;
            }
            const data = {
                riskAnalysis: riskAnalysis.value,
                browserInfo: browserInfo.value,
                cityInfo: cityInfo.value
            }
            axios.post('/api/detectbrowsertwo/store', data).then(function (response) {
                console.log("store response.data >>>>", response.data)
            })
        }

        const onGrecaptcha = ()=>{
            // e.preventDefault();
            grecaptcha.enterprise.ready(async () => {
                const token = await grecaptcha.enterprise.execute(siteKey, {action: user_action});
                if (token) {
                    var request = {
                        "event": {
                            "token": token,
                            "siteKey": siteKey,
                            "expectedAction": user_action,
                        }
                    }
                    console.log({"request >>>":request})
                    callRecaptchaenterprise(request)
                }
            });
        }

        const ipAddress_delect_duplicate    //ip地址重复检查
            = async () => {
            const Repsonse = await axios.post('/api/ip-records/check',{"ip_address":ipAddress.value})
            if (Repsonse.status == 200 && Repsonse.data.data.exists == true) {
                threat_potential_score.value = 0;

                vbRiskAnalysis.value = true;
                store();
            } else {
                onGrecaptcha();
            }
        }

        const detect_city = async() => {
            const cityRepsonse = await axios.post('/api/detect_city')
            if (cityRepsonse.status ==200) {
                cityInfo.value = cityRepsonse.data
                ipAddress.value = cityRepsonse.data.ip_address
                await ipAddress_delect_duplicate();
            }
        }


        onMounted(() => {

            detectBrowserAndOS()
            detect_city();
        })

        return {
            riskAnalysis,
            response,
            browserInfo,
            ipAddress,
            cityInfo,  // Add this line
            scoreClass,
            vbRiskAnalysis,
            threat_potential_score
        }
    }
}).mount('#app')



</script>

<style>
     .red-score {
        background-color: red;
        color: white;
        padding: 20px;
        margin: 10px;
    }
    .blue-score {
        background-color: blue;
        color: white;
        padding: 20px;
        margin: 10px;
    }
    .card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin: 20px auto;
        max-width: 600px;
        text-align: center;
    }
    .card-header {
        font-size: 1.2em;
        margin-bottom: 15px;
        font-weight: bold;
    }
    .card-content {
        margin: 10px 0;
    }
    .environ {
        margin-top: 20px;
    }
    .environ ul {
        list-style: none;
        padding: 0;
    }
    .environ li {
        margin: 10px 0;
        padding: 8px;
        background-color: #f5f5f5;
        border-radius: 4px;
    }
    .text-right {
        text-align: right;
    }
</style>
</html>
