<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detectbrowser</title>
    <!-- 引入 jQuery 库 -->
     <!-- 引入 Vue 3 -->
     <script src="/static/vue3/vue.global.js"></script>
    <!-- 引入 axios 用于发送HTTP请求 -->
    <script src="/static/vue3/axios.min.js"></script>

</head>
<body>
<div id="app">
    <div  class="card">
        <p style="text-align: right;">IP检查链接1</p>&nbsp;&nbsp;
        <div class="card-content" :class="scoreClass " v-if="cityInfo.ip_address">
            <p>@{{ cityInfo.ip_address }}</p>
            <p>
             @{{ cityInfo.country }} /@{{ cityInfo.subdivision }} / @{{ cityInfo.city }} / @{{ cityInfo.zipcode }}
            </p>
        </div>
    </div>

    <div class="card environ">
        <div class="card-content">
            <ul style="text-align: left;">
                <li>Enviromental Score: @{{ threat_potential_score }}</li>
                <li>Browser: @{{ browserInfo.browser }} / @{{ browserInfo.browser_version }} </li>
                <li>System Model: @{{ browserInfo.os }}/ @{{ browserInfo.os_version }}</li>
            </ul>
        </div>
    </div>

</div>
</body>
<script>
const { createApp, ref, computed } = Vue

const app = createApp({
    setup() {
        const response = ref(null)
        const browserUrl = '{{ $data["Local_Brower_Url"] }}'
        const userId = "kwwpxts";
        const ipAddress = ref(null)
        const browserInfo = ref({
            browser: '',
            os: '',
            version: ''
        })
        const threat_potential_score = ref(null)
        const cityInfo = ref({})

        const scoreClass = computed(() => {
            //如果Research defender的评分小于等于30，页面颜色用蓝色
            return threat_potential_score.value > 30 ? 'red-score' : 'blue-score'
        })


        const detect_city = async() => {
            const cityRepsonse = await axios.post('/api/detect_city')
            if (cityRepsonse.status ==200) {
                cityInfo.value = cityRepsonse.data
                ipAddress.value = cityRepsonse.data.ip_address
                await ipAddress_delect_duplicate();
            }
        }

        const ipAddress_delect_duplicate    //ip地址重复检查
        = async () => {

            const Repsonse = await axios.post('/api/ip-records/check',{"ip_address":ipAddress.value})
            if (Repsonse.status == 200 && Repsonse.data.data.exists == true) {
                threat_potential_score.value = 100;
                response.value = {
                    Respondent: {
                        threat_potential_score: 100
                    }
                };
                store();
            } else {
                await checkIpQuality()
            }
        }

        const checkIpQuality = async () => {
            const url = 'https://prod.rtymgt.com/api/v2/respondents/search/09489503-3059-4129-a9fa-734c31d0d111'
            console.log('IP质量检查请求URL:', url)
            try {
                const result = await axios.get(url)
                response.value = result.data
                threat_potential_score.value = response.value.Respondent.threat_potential_score
                store();
                console.log('响应数据:', response.value)
            } catch (error) {
                console.error('请求出错:', error)
            }
        }

        const store = () => {
            if (!cityInfo.value.ip_address || cityInfo.value.ip_address == '' || cityInfo.value.ip_address == undefined) {
                return;
            }
            const data = {
                riskAnalysis: response.value,
                browserInfo: browserInfo.value,
                cityInfo: cityInfo.value
            }
            axios.post('/api/detectbrowser/store', data).then(function (response) {
                console.log("store response.data >>>>", response.data)
            })
        }


        const getEnvInfo = () => {
            const ua = navigator.userAgent
            const browserRegex = {
                chrome: /chrome|chromium/i,
                firefox: /firefox/i,
                safari: /safari/i,
                edge: /edge/i,
                ie: /msie|trident/i
            }

            // 获取操作系统信息
            if (ua.includes('Windows')) {
                const osMatch = ua.match(/Windows NT ([\d.]+)/)
                browserInfo.value.os = 'Windows'
                browserInfo.value.os_version = osMatch ? osMatch[1]
                    .replace('6.1', '7')
                    .replace('6.2', '8')
                    .replace('6.3', '8.1')
                    .replace('10.0', '10') : ''
            } else if (ua.includes('Linux')) {
                browserInfo.value.os = 'Linux'
                browserInfo.value.os_version = ua.match(/Linux\s*([\w\d.]+)?/i)?.[1] || ''
            } else if (ua.includes('Mac')) {
                browserInfo.value.os = 'MacOS'
                browserInfo.value.os_version = ua.match(/Mac OS X\s*([\w\d_.]+)?/i)?.[1]?.replace(/_/g, '.') || ''
            }

            // 获取浏览器类型和版本
            for (let browser in browserRegex) {
                if (browserRegex[browser].test(ua)) {
                    browserInfo.value.browser = browser
                    const version = ua.match(/(?:chrome|firefox|safari|edge|msie|rv(?=:))\/?\s*(\d+)/i)
                    browserInfo.value.browser_version = version ? version[1] : ''
                    break
                }
            }
        }
        detect_city();

        getEnvInfo();

        return {
            response,
            scoreClass,
            ipAddress,
            browserInfo,
            cityInfo,
            threat_potential_score
        }
    }
})

app.mount('#app')
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
</style>
</html>
