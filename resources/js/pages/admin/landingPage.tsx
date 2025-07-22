import { usePage } from "@inertiajs/react";

export default function LandingPage() {
    const { clientCount, serverCount } = usePage().props;

    console.log(clientCount, serverCount);
    const cardCSS = "border-2 rounded-lg p-4 h-30 mx-32 text-center";
    return (
        <>
            <div className="flex flex-row justify-center px-52 mx-auto mt-14">
                <div className={cardCSS}>
                    <h2 className="text-lg">Nombre d'utilisateur</h2>
                    <p className="text-2xl mt-4">{clientCount}</p>
                </div>
                <div className={cardCSS}>
                    <h2 className="text-lg">Nombre de serveur</h2>
                    <p className="text-2xl mt-4">{serverCount}</p>
                </div>
                <div>

                </div>
            </div>
        </>
    )
}