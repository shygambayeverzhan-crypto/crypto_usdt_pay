import "dotenv/config";
import { TronWeb } from "tronweb";

function out(obj) {
    process.stdout.write(JSON.stringify(obj) + "\n");
}

function fail(code, message, extra = {}) {
    out({ ok: false, code, error: message, ...extra });
    process.exit(1);
}

function getArg(i, name) {
    const v = process.argv[i];
    if (!v) fail("VALIDATION_ERROR", `Missing argument: ${name}`);
    return v;
}

function normalizeWalletId(id) {
    return id.trim().toUpperCase();
}

function getNetwork() {
    // можно передать как 5-й аргумент: nile
    const argNet = process.argv[6];
    const net = (argNet || process.env.TRON_NETWORK || "nile").toLowerCase();
    if (net !== "nile") fail("CONFIG_ERROR", `Unsupported network: ${net}`);
    return net;
}

function getCfg(network) {
    if (network === "nile") {
        const fullHost = process.env.TRON_NILE_FULLHOST;
        const apiKey = process.env.TRON_NILE_API_KEY;
        const usdt = process.env.TRON_NILE_USDT_CONTRACT;

        if (!fullHost) fail("CONFIG_ERROR", "Missing env: TRON_NILE_FULLHOST");
        if (!usdt) fail("CONFIG_ERROR", "Missing env: TRON_NILE_USDT_CONTRACT");

        return { fullHost, apiKey, usdt };
    }
    fail("CONFIG_ERROR", `Unsupported network: ${network}`);
}

function getWallet(walletIdUpper) {
    const mnemonic = process.env[`WALLET_${walletIdUpper}_MNEMONIC`];
    const path = process.env[`WALLET_${walletIdUpper}_PATH`] || "m/44'/195'/0'/0/0";
    if (!mnemonic) fail("CONFIG_ERROR", `Unknown walletId or missing env: WALLET_${walletIdUpper}_MNEMONIC`);
    return { mnemonic, path };
}

// Без float-ошибок: "1.23" + decimals=6 => 1230000n
function amountToRawBigInt(amountStr, decimals) {
    const s = String(amountStr).trim();
    if (!/^\d+(\.\d+)?$/.test(s)) fail("VALIDATION_ERROR", `Invalid amount: ${amountStr}`);

    const [intPart, fracPartRaw = ""] = s.split(".");
    const fracPart = fracPartRaw.slice(0, decimals).padEnd(decimals, "0"); // обрезаем лишнее, дополняем нулями

    const raw = (intPart + fracPart).replace(/^0+/, "") || "0";
    return BigInt(raw);
}

function isTronAddress(tronWeb, addr) {
    // tronweb обычно предоставляет tronWeb.isAddress
    if (tronWeb && typeof tronWeb.isAddress === "function") return tronWeb.isAddress(addr);
    // fallback: простая проверка префикса
    return typeof addr === "string" && addr.startsWith("T") && addr.length >= 30;
}

async function main() {
    // usage:
    // node send_usdt_cli.js <walletId> <to> <amount> <idempotencyKey> [network]
    const walletId = getArg(2, "walletId");           // пример: test_wallet_1
    const to = getArg(3, "to");                       // адрес T...
    const amount = getArg(4, "amount");               // "1.00"
    const idempotencyKey = getArg(5, "idempotencyKey");

    const network = getNetwork();
    const cfg = getCfg(network);

    const walletIdUpper = normalizeWalletId(walletId);
    const { mnemonic, path } = getWallet(walletIdUpper);

    // В v6: TronWeb.fromMnemonic(...)
    const acc = TronWeb.fromMnemonic(mnemonic, path, "");
    const pk = acc.privateKey?.startsWith("0x") ? acc.privateKey.slice(2) : acc.privateKey;

    if (!pk) fail("SIGN_ERROR", "Failed to derive privateKey from mnemonic", { walletId, path });

    const tronWeb = new TronWeb({
        fullHost: cfg.fullHost,
        headers: cfg.apiKey ? { "TRON-PRO-API-KEY": cfg.apiKey } : {},
        privateKey: pk,
    });

    if (!isTronAddress(tronWeb, to)) {
        fail("VALIDATION_ERROR", `Invalid TRON address: ${to}`, { to });
    }

    let contract;
    try {
        contract = await tronWeb.contract().at(cfg.usdt);
    } catch (e) {
        fail("TRON_ERROR", `Failed to load contract: ${cfg.usdt}`, { details: String(e?.message || e) });
    }

    let decimals;
    try {
        decimals = Number(await contract.decimals().call());
        if (!Number.isFinite(decimals) || decimals < 0 || decimals > 30) {
            fail("TRON_ERROR", `Bad decimals from contract: ${decimals}`);
        }
    } catch (e) {
        fail("TRON_ERROR", "Failed to read decimals()", { details: String(e?.message || e) });
    }

    const rawValue = amountToRawBigInt(amount, decimals);

    try {
        const txid = await contract.transfer(to, rawValue.toString()).send({
            feeLimit: 30_000_000,
        });

        out({
            ok: true,
            status: "broadcasted",
            network,
            walletId,
            from: acc.address,
            to,
            amount,
            decimals,
            amountRaw: rawValue.toString(),
            idempotencyKey,
            txid,
        });

        process.exit(0);
    } catch (e) {
        fail("TRON_ERROR", "Transfer failed", {
            network,
            walletId,
            from: acc.address,
            to,
            amount,
            decimals,
            amountRaw: rawValue.toString(),
            idempotencyKey,
            details: String(e?.message || e),
        });
    }
}

main().catch((e) => {
    fail("UNEXPECTED_ERROR", "Unexpected error", { details: String(e?.message || e) });
});
