export function pretty(obj: unknown) {
    try {
        const json = JSON.stringify(obj, null, 2)
        return json.split('\n').map(line => '    ' + line).join('\n')
    } catch {
        return String(obj)
    }
}

export function buildQuery(params: Record<string, unknown>): string {
    const query = new URLSearchParams()

    Object.entries(params).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return
        }

        if (typeof value === 'boolean') {
            if (value) {
                query.append(key, '1')
            }
            return
        }

        const asString = String(value).trim()
        if (asString !== '') {
            query.append(key, asString)
        }
    })

    const qs = query.toString()
    return qs ? `?${qs}` : ''
}

export function createApiClient(apiBase: string, apiKey: string) {
    async function requestJson(path: string, init?: RequestInit) {
        const res = await fetch(apiBase + path, {
            ...init,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Api-Key': apiKey ?? '',
                ...(init?.headers || {}),
            },
        })
        const isJson = res.headers.get('content-type')?.includes('application/json')
        const body = isJson ? await res.json() : await res.text()
        return { ok: res.ok, status: res.status, body } as const
    }

    async function requestBlob(path: string) {
        const res = await fetch(apiBase + path, {
            headers: {
                'Accept': 'application/json',
                'X-Api-Key': apiKey ?? '',
            },
        })
        const blob = await res.blob()
        return { ok: res.ok, status: res.status, blob } as const
    }

    return { requestJson, requestBlob } as const
}


