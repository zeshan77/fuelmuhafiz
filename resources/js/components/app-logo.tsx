export default function AppLogo() {
    return (
        <>
            <div className="bg-lime text-lime-foreground flex aspect-square size-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold tracking-tight">
                FM
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-sm leading-tight font-semibold">
                    FuelMuhafiz
                </span>
                <span className="text-sidebar-foreground/60 truncate text-[10px] leading-tight font-medium tracking-[0.12em] uppercase">
                    Platform console
                </span>
            </div>
        </>
    );
}
