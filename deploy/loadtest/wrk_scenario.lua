-- wrk Load Test Scenario
-- Run: wrk -t12 -c400 -d60s -s wrk_scenario.lua https://yourdomain.com

wrk.method = "GET"
wrk.headers["User-Agent"] = "wrk/loadtest"

request = function()
    local paths = {
        "/",
        "/products",
        "/categories",
        "/health",
        "/products/deals",
        "/products/sellers",
    }
    local path = paths[math.random(#paths)]
    return wrk.format("GET", path)
end

response = function(status, headers, body)
    if status ~= 200 then
        print("Non-200 response: " .. status)
    end
end
